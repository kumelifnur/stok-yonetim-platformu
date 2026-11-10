<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $business = $request->user()->business()->firstOrFail();
        $products = $business->products()->where('is_active', true)->orderBy('name')->get();
        $lowStock = $business->products()->where('is_active', true)->whereColumn('stock_on_hand', '<=', 'reorder_level')->orderBy('stock_on_hand')->limit(6)->get();
        $sales = $business->sales()->with('lines')->latest()->limit(8)->get();
        $movements = $business->stockMovements()->with('sale')->latest()->limit(10)->get();
        $inventoryValue = $business->products()->where('is_active', true)->sum(DB::raw('stock_on_hand * COALESCE(unit_cost, 0)'));
        $pendingCount = $business->sales()->where('status', 'pending')->count();

        return view('dashboard.index', compact('business', 'products', 'lowStock', 'sales', 'movements', 'inventoryValue', 'pendingCount'));
    }
}
