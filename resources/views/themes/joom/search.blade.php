@extends('main_layots')

@section('title', $q ? 'Поиск: '.$q.' — BIMM' : 'Поиск — BIMM')

@section('content')
<nav class="joom-breadcrumbs" aria-label="Хлебные крошки"><a href="{{ $regionalUrl->route('home') }}">Главная</a><span>Поиск</span></nav>
<header class="joom-page-head"><span class="joom-eyebrow">ПОИСК</span><h1>{{ $q ? 'Результаты для «'.$q.'»' : 'Найдите свой товар' }}</h1><p>{{ $products->total() }} предложений в каталоге.</p></header>
<form class="joom-search joom-search--page" action="{{ $regionalUrl->route('search') }}" method="GET" role="search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" value="{{ $q }}" placeholder="Название товара" aria-label="Название товара"><button type="submit">Найти</button></form>
@if($products->isNotEmpty())
    <div class="joom-catalog-grid joom-search-grid">@foreach($products as $product) @include('components.home-product-card', ['product' => $product]) @endforeach</div>
    @if($products->hasPages())<div class="joom-pagination">{{ $products->links('vendor.pagination.custom') }}</div>@endif
@else
    <div class="joom-empty"><i class="fa-solid fa-magnifying-glass"></i><h2>Ничего не найдено</h2><p>Попробуйте другой запрос — например, «наушники» или «платье».</p><a class="joom-button joom-button--primary" href="{{ $regionalUrl->route('catalog.index') }}">Открыть каталог</a></div>
@endif
@endsection
