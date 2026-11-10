@extends('layouts.app', ['title' => 'Yeni sipariş — '.$business->name])
@section('content')
<section class="dashboard-head"><div><p class="eyebrow">SATIŞ İŞLEMİ</p><h1>Yeni sipariş</h1><p class="muted">Sipariş kaydedilince ürün miktarları ayrılır.</p></div><a class="button secondary" href="{{ route('dashboard') }}">← Panele dön</a></section>
@if($products->isEmpty())<div class="empty-state panel"><span>▦</span><b>Satışa açık stokta ürün yok</b><p>Önce panele ürün ve başlangıç stoğu ekle.</p><a class="button secondary" href="{{ route('dashboard') }}">Ürün paneline git</a></div>@else
<form class="panel sale-form" method="POST" action="{{ route('sales.store') }}">@csrf
    <div class="form-section-head"><span class="step-number">01</span><div><h2>Sipariş bilgileri</h2><p>Müşteri alanları isteğe bağlıdır.</p></div></div>
    <div class="form-two"><label>Müşteri adı<input name="customer_name" value="{{ old('customer_name') }}" maxlength="120"></label><label>Müşteri e-postası<input name="customer_email" type="email" value="{{ old('customer_email') }}" maxlength="180"></label></div>
    <div class="form-section-head items-head"><span class="step-number">02</span><div><h2>Ürün satırları</h2><p>Her ürün için miktar seç. Fiyat ve stok sunucuda tekrar kontrol edilir.</p></div><button class="button secondary" id="addItem" type="button">＋ Satır ekle</button></div>
    <div id="items" class="items-list">
        @foreach(old('items', [['product_id' => '', 'quantity' => 1]]) as $index => $oldItem)
            <div class="item-row"><label>Ürün<select name="items[{{ $index }}][product_id]" required><option value="">Ürün seç</option>@foreach($products as $product)<option value="{{ $product->id }}" data-stock="{{ $product->stock_on_hand }}" data-price="{{ $product->unit_price }}" @selected((string)($oldItem['product_id'] ?? '') === (string)$product->id)>{{ $product->name }} · {{ $product->sku }} · {{ $product->stock_on_hand }} adet</option>@endforeach</select></label><label>Miktar<input name="items[{{ $index }}][quantity]" type="number" min="1" value="{{ $oldItem['quantity'] ?? 1 }}" required></label><button class="remove-row" type="button" aria-label="Satırı kaldır">×</button></div>
        @endforeach
    </div>
    <div class="sale-total-preview"><span>Tarayıcıdaki yaklaşık toplam</span><strong id="totalPreview">₺0,00</strong><small>Kaydetmeden önce fiyatları sunucu yeniden hesaplar.</small></div>
    <label class="note-field">Sipariş notu <span class="muted">(isteğe bağlı)</span><textarea name="note" rows="3" maxlength="1000">{{ old('note') }}</textarea></label>
    <div class="sale-submit"><a class="text-link" href="{{ route('dashboard') }}">Vazgeç</a><button class="button primary" type="submit">Siparişi oluştur <span>↗</span></button></div>
</form>
<script>
(() => {
    const list = document.getElementById('items');
    const add = document.getElementById('addItem');
    const total = document.getElementById('totalPreview');
    const products = @json($products->map(fn($product) => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'stock' => $product->stock_on_hand, 'price' => $product->unit_price])->values());
    let nextIndex = list.querySelectorAll('.item-row').length;
    const updateTotal = () => {
        let cents = 0;
        list.querySelectorAll('.item-row').forEach(row => {
            const option = row.querySelector('select').selectedOptions[0];
            const qty = Number(row.querySelector('input').value || 0);
            cents += Math.round(Number(option?.dataset.price || 0) * 100) * qty;
        });
        total.textContent = new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(cents / 100);
    };
    const addRow = () => {
        const row = document.createElement('div'); row.className = 'item-row';
        const productLabel = document.createElement('label'); productLabel.textContent = 'Ürün';
        const select = document.createElement('select'); select.name = `items[${nextIndex}][product_id]`; select.required = true;
        const placeholder = document.createElement('option'); placeholder.value = ''; placeholder.textContent = 'Ürün seç'; select.appendChild(placeholder);
        products.forEach(product => { const option = document.createElement('option'); option.value = product.id; option.dataset.stock = product.stock; option.dataset.price = product.price; option.textContent = `${product.name} · ${product.sku} · ${product.stock} adet`; select.appendChild(option); });
        productLabel.appendChild(select);
        const quantityLabel = document.createElement('label'); quantityLabel.textContent = 'Miktar';
        const quantity = document.createElement('input'); quantity.type = 'number'; quantity.min = '1'; quantity.value = '1'; quantity.required = true; quantity.name = `items[${nextIndex}][quantity]`; quantityLabel.appendChild(quantity);
        const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'remove-row'; remove.setAttribute('aria-label', 'Satırı kaldır'); remove.textContent = '×';
        row.append(productLabel, quantityLabel, remove); list.appendChild(row); nextIndex++; updateTotal();
    };
    add.addEventListener('click', addRow);
    list.addEventListener('input', updateTotal);
    list.addEventListener('change', updateTotal);
    list.addEventListener('click', event => { if (event.target.closest('.remove-row')) { const rows = list.querySelectorAll('.item-row'); if (rows.length > 1) event.target.closest('.item-row').remove(); updateTotal(); } });
    updateTotal();
})();
</script>
@endif
@endsection
