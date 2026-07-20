@extends('main_layots')

@section('title', 'Скидки — BIMM')

@section('content')
<header class="bimm-page-head"><span class="bimm-kicker">Особая цена</span><h1>Скидки и находки</h1><p>Товары, которые сейчас можно купить выгоднее.</p></header>
@if($products->isNotEmpty())
    <div class="bimm-product-grid">@foreach($products as $product) @include('components.home-product-card', ['product' => $product]) @endforeach</div>
    @if($products->hasPages())<div class="bimm-pagination">{{ $products->links('vendor.pagination.custom') }}</div>@endif
@else
    <div class="bimm-empty"><p>Активных скидок пока нет. Загляните в каталог — там уже есть новые предложения.</p><a class="bimm-button bimm-button--dark" href="{{ $regionalUrl->route('catalog.index') }}">Открыть каталог</a></div>
@endif
@endsection
