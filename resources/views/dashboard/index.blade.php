@extends('layouts.app', ['title' => $business->name.' — Stok paneli'])
@php
    $statusNames = ['pending' => 'Bekliyor', 'completed' => 'Tamamlandı', 'cancelled' => 'İptal edildi'];
    $movementNames = ['opening' => 'Açılış stoğu', 'received' => 'Stok girişi', 'adjusted_out' => 'Stok düzeltme', 'reserved' => 'Sipariş için ayrıldı', 'released' => 'Sipariş iptali iadesi'];
@endphp
@section('content')
<section class="dashboard-head"><div><p class="eyebrow">İŞLETME PANELİ</p><h1>{{ $business->name }}</h1><p class="muted">Ürün, sipariş ve stok hareketlerin burada.</p></div><a class="button primary" href="{{ route('sales.create') }}">＋ Yeni sipariş</a></section>
<section class="stat-grid"><article class="stat-card"><span>Aktif ürün</span><strong>{{ $products->count() }}</strong><small>Katalogda listelenen</small></article><article class="stat-card"><span>Düşük stok</span><strong class="{{ $lowStock->count() ? 'stat-warn' : '' }}">{{ $lowStock->count() }}</strong><small>Yeniden sipariş eşiğinde</small></article><article class="stat-card"><span>Bekleyen sipariş</span><strong>{{ $pendingCount }}</strong><small>Stokları geçici olarak ayrıldı</small></article><article class="stat-card"><span>Stok maliyeti</span><strong>₺{{ number_format((float) $inventoryValue, 2, ',', '.') }}</strong><small>Maliyet girilmiş ürünler</small></article></section>

<section class="dashboard-grid">
    <div class="panel product-panel">
        <div class="section-heading"><div><h2>Ürün kataloğu</h2><p>Stok miktarı, düşük stok eşiği ve satış fiyatları.</p></div><span class="count-pill">{{ $products->count() }} ürün</span></div>
        @if($products->isEmpty())<div class="empty-state"><span>▦</span><b>Henüz ürün eklenmedi</b><p>İlk ürününü sağdaki formdan ekleyebilirsin.</p></div>@else
        <div class="table-wrap"><table class="product-table"><thead><tr><th>ÜRÜN / SKU</th><th>STOK</th><th>FİYAT</th><th>AYARLAR</th></tr></thead><tbody>
        @foreach($products as $product)
            <tr><td><b>{{ $product->name }}</b><small>{{ $product->sku }}</small></td><td><span class="stock-pill {{ $product->stock_on_hand <= $product->reorder_level ? 'stock-low' : '' }}">{{ $product->stock_on_hand }} adet</span><small>Eşik: {{ $product->reorder_level }}</small></td><td>₺{{ number_format((float) $product->unit_price, 2, ',', '.') }}</td><td><details class="product-actions"><summary>Düzenle</summary><div class="product-popover">
                <form class="compact-form" method="POST" action="{{ route('products.update', $product) }}">@csrf @method('PUT')<h3>Ürün bilgileri</h3><label>SKU<input name="sku" value="{{ $product->sku }}" required maxlength="50"></label><label>Ad<input name="name" value="{{ $product->name }}" required maxlength="140"></label><div class="form-two"><label>Satış fiyatı<input name="unit_price" type="number" step="0.01" min="0" value="{{ $product->unit_price }}" required></label><label>Birim maliyet<input name="unit_cost" type="number" step="0.01" min="0" value="{{ $product->unit_cost }}"></label></div><label>Yeniden sipariş eşiği<input name="reorder_level" type="number" min="0" value="{{ $product->reorder_level }}" required></label><label>Açıklama<textarea name="description" rows="2" maxlength="1000">{{ $product->description }}</textarea></label><button class="button secondary full" type="submit">Kaydet</button></form>
                <form class="compact-form" method="POST" action="{{ route('products.stock', $product) }}">@csrf<h3>Stok hareketi</h3><div class="form-two"><label>Hareket<select name="direction"><option value="in">Stok girişi</option><option value="out">Stok çıkışı</option></select></label><label>Miktar<input name="quantity" type="number" min="1" value="1" required></label></div><label>Açıklama<input name="note" maxlength="500" placeholder="Örn. Tedarik alımı"></label><button class="button secondary full" type="submit">Hareketi kaydet</button></form>
            </div></details></td></tr>
        @endforeach
        </tbody></table></div>@endif
    </div>

    <div class="panel side-panel">
        <div class="section-heading"><div><h2>Yeni ürün</h2><p>Ürün açarken başlangıç stokunu da ekle.</p></div></div>
        <form class="compact-form new-product-form" method="POST" action="{{ route('products.store') }}">@csrf
            <label>Ürün adı<input name="name" value="{{ old('name') }}" required maxlength="140" placeholder="Örn. Seramik kupa"></label>
            <label>SKU / stok kodu<input name="sku" value="{{ old('sku') }}" required maxlength="50" placeholder="KUPA-001"></label>
            <div class="form-two"><label>Satış fiyatı (₺)<input name="unit_price" type="number" step="0.01" min="0" value="{{ old('unit_price') }}" required></label><label>Birim maliyet (₺)<input name="unit_cost" type="number" step="0.01" min="0" value="{{ old('unit_cost') }}"></label></div>
            <div class="form-two"><label>Açılış stoğu<input name="opening_stock" type="number" min="0" value="{{ old('opening_stock', 0) }}" required></label><label>Düşük stok eşiği<input name="reorder_level" type="number" min="0" value="{{ old('reorder_level', 5) }}" required></label></div>
            <label>Kısa açıklama<textarea name="description" maxlength="1000" rows="2">{{ old('description') }}</textarea></label>
            <button class="button primary full" type="submit">Ürünü ekle <span>↗</span></button>
        </form>
    </div>

    <div class="panel sales-panel"><div class="section-heading"><div><h2>Son siparişler</h2><p>Bekleyen siparişlerde stok ayrılmıştır.</p></div><a class="text-link" href="{{ route('sales.create') }}">Yeni sipariş ↗</a></div>
        @forelse($sales as $sale)
            <article class="sale-row"><div class="sale-number"><b>{{ $sale->order_number }}</b><small>{{ $sale->created_at->format('d.m.Y H:i') }}</small></div><div class="sale-customer"><b>{{ $sale->customer_name ?: 'Müşteri belirtilmedi' }}</b><small>{{ $sale->lines->count() }} ürün satırı</small></div><strong class="sale-total">₺{{ number_format((float) $sale->total, 2, ',', '.') }}</strong><span class="status status-{{ $sale->status }}">{{ $statusNames[$sale->status] ?? $sale->status }}</span>
                @if($sale->status === 'pending')<div class="sale-actions"><form method="POST" action="{{ route('sales.status', $sale) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="completed"><button class="small-action confirm" type="submit">Tamamla</button></form><form method="POST" action="{{ route('sales.status', $sale) }}" onsubmit="return confirm('Siparişi iptal edip ayrılan stokları geri ekleyeyim mi?')">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled"><button class="small-action" type="submit">İptal et</button></form></div>@endif
            </article>
        @empty<div class="empty-inline">Henüz sipariş yok. Ürün ekledikten sonra yeni sipariş oluşturabilirsin.</div>@endforelse
    </div>

    <div class="panel low-panel"><div class="section-heading"><div><h2>Düşük stok uyarısı</h2><p>Stok eşiğine ulaşmış ürünler.</p></div></div>
        @forelse($lowStock as $product)<div class="low-row"><div><b>{{ $product->name }}</b><small>{{ $product->sku }}</small></div><strong>{{ $product->stock_on_hand }} / {{ $product->reorder_level }}</strong></div>@empty<div class="empty-inline">Eşiğin altında ürün yok.</div>@endforelse
    </div>

    <div class="panel movement-panel"><div class="section-heading"><div><h2>Son stok hareketleri</h2><p>Stok miktarındaki değişikliklerin geçmişi.</p></div></div>
        @forelse($movements as $movement)<div class="movement-row"><span class="movement-icon {{ $movement->quantity_delta > 0 ? 'plus' : 'minus' }}">{{ $movement->quantity_delta > 0 ? '+' : '−' }}</span><div><b>{{ $movement->product_name }}</b><small>{{ $movementNames[$movement->type] ?? $movement->type }}@if($movement->sale) · {{ $movement->sale->order_number }}@endif</small></div><strong class="{{ $movement->quantity_delta > 0 ? 'qty-plus' : 'qty-minus' }}">{{ $movement->quantity_delta > 0 ? '+' : '' }}{{ $movement->quantity_delta }}</strong><small class="after-stock">Kalan {{ $movement->quantity_after }}</small></div>@empty<div class="empty-inline">Henüz stok hareketi yok.</div>@endforelse
    </div>
</section>
@endsection
