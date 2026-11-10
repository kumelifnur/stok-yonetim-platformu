<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#f5f4ef">
    <title>{{ $title ?? 'Depo — Stok ve sipariş yönetimi' }}</title><link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="site-header"><a class="brand" href="{{ route('home') }}">depo<span>.</span></a><nav class="nav-links">
    @auth
        @if(auth()->user()->business)<a href="{{ route('dashboard') }}">Kontrol paneli</a>@endif
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-button" type="submit">Çıkış</button></form>
    @else<a href="{{ route('login') }}">Giriş yap</a><a class="nav-cta" href="{{ route('register') }}">İşletme hesabı aç</a>@endauth
</nav></header>
@if(session('success'))<div class="flash success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="flash error" role="alert"><strong>Formu kontrol et:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<main>@yield('content')</main>
<footer class="site-footer">Depo · Küçük işletmeler için ürün ve sipariş takibi</footer>
</body>
</html>
