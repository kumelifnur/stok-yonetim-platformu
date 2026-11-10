<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Sale;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function create(Request $request): View
    {
        $business = $request->user()->business()->firstOrFail();
        $products = $business->products()->where('is_active', true)->where('stock_on_hand', '>', 0)->orderBy('name')->get();

        return view('sales.create', compact('business', 'products'));
    }

    public function store(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_email' => ['nullable', 'email', 'max:180'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.product_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        $business = $request->user()->business()->firstOrFail();
        $sale = $inventory->createSale($business, $data);

        return redirect()->route('dashboard')->with('success', 'Sipariş '.$sale->order_number.' oluşturuldu; stok ayrıldı.');
    }

    public function updateStatus(Request $request, Sale $sale, InventoryService $inventory): RedirectResponse
    {
        $business = $request->user()->business()->firstOrFail();
        abort_unless($sale->business_id === $business->id, 404);
        $data = $request->validate(['status' => ['required', 'in:completed,cancelled']]);

        $inventory->changeSaleStatus($business, $sale->id, $data['status']);

        $message = $data['status'] === 'cancelled'
            ? 'Sipariş iptal edildi ve ayrılan stok geri eklendi.'
            : 'Sipariş tamamlandı.';

        return back()->with('success', $message);
    }
}
