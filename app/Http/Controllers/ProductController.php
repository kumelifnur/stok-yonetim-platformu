<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->business()->firstOrFail();
        $request->merge(['sku' => strtoupper(trim((string) $request->input('sku')))]);
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->where('business_id', $business->id)],
            'name' => ['required', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'opening_stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);

        DB::transaction(function () use ($business, $data): void {
            $product = $business->products()->create([
                'sku' => strtoupper($data['sku']),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'unit_price' => $data['unit_price'],
                'unit_cost' => $data['unit_cost'] ?? null,
                'stock_on_hand' => $data['opening_stock'],
                'reorder_level' => $data['reorder_level'],
            ]);

            if ((int) $product->stock_on_hand > 0) {
                $product->movements()->create([
                    'business_id' => $business->id,
                    'product_name' => $product->name,
                    'type' => 'opening',
                    'quantity_delta' => (int) $product->stock_on_hand,
                    'quantity_after' => (int) $product->stock_on_hand,
                    'note' => 'Açılış stoku.',
                ]);
            }
        });

        return back()->with('success', 'Ürün eklendi.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $business = $request->user()->business()->firstOrFail();
        abort_unless($product->business_id === $business->id, 404);
        $request->merge(['sku' => strtoupper(trim((string) $request->input('sku')))]);

        $data = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->where('business_id', $business->id)->ignore($product->id)],
            'name' => ['required', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);

        $product->update($data);

        return back()->with('success', 'Ürün bilgileri güncellendi.');
    }

    public function adjustStock(Request $request, Product $product): RedirectResponse
    {
        $business = $request->user()->business()->firstOrFail();
        abort_unless($product->business_id === $business->id, 404);
        $data = $request->validate([
            'direction' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($business, $product, $data): void {
            $locked = $business->products()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $delta = $data['direction'] === 'in' ? (int) $data['quantity'] : -(int) $data['quantity'];
            $after = (int) $locked->stock_on_hand + $delta;
            if ($after < 0) {
                throw ValidationException::withMessages(['quantity' => 'Stok sıfırın altına inemez.']);
            }

            $locked->update(['stock_on_hand' => $after]);
            $locked->movements()->create([
                'business_id' => $business->id,
                'product_name' => $locked->name,
                'type' => $delta > 0 ? 'received' : 'adjusted_out',
                'quantity_delta' => $delta,
                'quantity_after' => $after,
                'note' => $data['note'] ?? null,
            ]);
        }, attempts: 3);

        return back()->with('success', 'Stok hareketi kaydedildi.');
    }
}
