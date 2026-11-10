<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function createSale(Business $business, array $data): Sale
    {
        return DB::transaction(function () use ($business, $data): Sale {
            $lockedBusiness = Business::query()->whereKey($business->id)->lockForUpdate()->firstOrFail();
            $items = collect($data['items'])->sortBy(fn (array $item) => (int) $item['product_id'])->values();
            $productIds = $items->pluck('product_id')->map(fn ($id) => (int) $id)->all();

            $products = $lockedBusiness->products()
                ->whereIn('id', $productIds)
                ->where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds)) {
                throw ValidationException::withMessages(['items' => 'Bir veya daha fazla ürün bu işletmeye ait değil ya da satışa kapalı.']);
            }

            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);
                if ((int) $product->stock_on_hand < (int) $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => $product->name.' için yeterli stok yok. Mevcut stok: '.$product->stock_on_hand.'.',
                    ]);
                }
            }

            $today = now()->format('Ymd');
            $sequence = $lockedBusiness->sales()->where('order_number', 'like', 'ST-'.$today.'-%')->count() + 1;
            $sale = $lockedBusiness->sales()->create([
                'order_number' => sprintf('ST-%s-%04d', $today, $sequence),
                'customer_name' => $data['customer_name'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'note' => $data['note'] ?? null,
                'total' => 0,
                'status' => 'pending',
            ]);

            $totalCents = 0;
            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];
                $unitPriceCents = (int) round((float) $product->unit_price * 100);
                $lineTotalCents = $unitPriceCents * $quantity;
                $totalCents += $lineTotalCents;
                $after = (int) $product->stock_on_hand - $quantity;

                $sale->lines()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $this->money($unitPriceCents),
                    'line_total' => $this->money($lineTotalCents),
                ]);

                $product->update(['stock_on_hand' => $after]);
                $product->movements()->create([
                    'business_id' => $lockedBusiness->id,
                    'sale_id' => $sale->id,
                    'product_name' => $product->name,
                    'type' => 'reserved',
                    'quantity_delta' => -$quantity,
                    'quantity_after' => $after,
                    'note' => 'Sipariş '.$sale->order_number.' için stok ayrıldı.',
                ]);
            }

            $sale->update(['total' => $this->money($totalCents)]);

            return $sale->load('lines');
        }, attempts: 3);
    }

    public function changeSaleStatus(Business $business, int $saleId, string $status): Sale
    {
        return DB::transaction(function () use ($business, $saleId, $status): Sale {
            $sale = $business->sales()->whereKey($saleId)->lockForUpdate()->firstOrFail();
            if ($sale->status !== 'pending' || ! in_array($status, ['completed', 'cancelled'], true)) {
                throw ValidationException::withMessages(['sale' => 'Bu sipariş için seçilen durum değişikliğine izin verilmiyor.']);
            }

            if ($status === 'cancelled') {
                $lines = $sale->lines()->orderBy('product_id')->get();
                $productIds = $lines->pluck('product_id')->filter()->unique()->sort()->values();
                $products = Product::query()
                    ->where('business_id', $business->id)
                    ->whereIn('id', $productIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($lines as $line) {
                    $product = $products->get($line->product_id);
                    if (! $product) {
                        continue;
                    }

                    $quantity = (int) $line->quantity;
                    $after = (int) $product->stock_on_hand + $quantity;
                    $product->update(['stock_on_hand' => $after]);
                    $product->movements()->create([
                        'business_id' => $business->id,
                        'sale_id' => $sale->id,
                        'product_name' => $line->product_name,
                        'type' => 'released',
                        'quantity_delta' => $quantity,
                        'quantity_after' => $after,
                        'note' => 'İptal edilen '.$sale->order_number.' siparişinin stoğu serbest bırakıldı.',
                    ]);
                }
            }

            $sale->update(['status' => $status]);

            return $sale;
        }, attempts: 3);
    }

    private function money(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
