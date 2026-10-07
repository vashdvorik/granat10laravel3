@extends('main_layots')

@section('title', 'Избранное — BIMM')

@section('content')
<header class="bimm-page-head"><span class="bimm-kicker">Ваш выбор</span><h1>Избранное</h1><p>Сохраняйте интересные товары, чтобы сравнить их позже. Список хранится в этом браузере.</p></header>
<div id="favorites-loading" class="bimm-empty">Загружаем избранное…</div>
<div id="favorites-grid" style="display:none"><div id="favorites-products-row" class="bimm-product-grid"></div></div>
<div id="favorites-empty" class="bimm-empty" style="display:none"><i class="fa-regular fa-heart" style="font-size:32px;margin-bottom:12px"></i><h2>Список пока пуст</h2><p>Нажимайте на сердечко в карточке товара — так вы не потеряете подходящие варианты.</p><a class="bimm-button bimm-button--dark" href="{{ $regionalUrl->route('catalog.index') }}">Найти товары</a></div>
@if($recommendedProducts->isNotEmpty())<section class="bimm-section" id="favorites-recommendations"><div class="bimm-section__head"><div><span class="bimm-kicker">Вдохновение</span><h2>Можно начать с этого</h2></div></div><div class="bimm-product-grid">@foreach($recommendedProducts as $product) @include('components.home-product-card', ['product' => $product]) @endforeach</div></section>@endif
@endsection

@push('scripts')
<script>
(function(){var ids=[];try{ids=JSON.parse(localStorage.getItem('favorites')||'[]')}catch(e){}ids=Array.isArray(ids)?ids:[];var loading=document.getElementById('favorites-loading'),grid=document.getElementById('favorites-grid'),empty=document.getElementById('favorites-empty');if(!ids.length){loading.style.display='none';empty.style.display='block';return;}fetch('{{ $regionalUrl->route('favorites.products', [], false) }}?ids='+ids.join(','),{headers:{Accept:'application/json'}}).then(function(r){return r.json()}).then(function(data){loading.style.display='none';if(!data.html){empty.style.display='block';return;}document.getElementById('favorites-products-row').innerHTML=data.html;grid.style.display='block';window.dispatchEvent(new Event('bimm:favorites-rendered'));}).catch(function(){loading.style.display='none';empty.style.display='block';});})();
</script>
@endpush
