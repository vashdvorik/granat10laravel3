@extends('main_layots')

@section('title', $category->name.' — BIMM')

@section('content')
<nav class="bimm-breadcrumbs" aria-label="Хлебные крошки"><a href="{{ $regionalUrl->route('home') }}">Главная</a><a href="{{ $regionalUrl->route('catalog.index') }}">Каталог</a>@foreach($breadcrumbs as $crumb)<a href="{{ $regionalUrl->route('catalog.show', ['alias' => $crumb->alias]) }}">{{ $crumb->name }}</a>@endforeach</nav>
<header class="bimm-page-head"><span class="bimm-kicker">Категория</span><h1>{{ $category->name }}</h1><p>{{ $category->desc ?: 'Подборка товаров и предложений от локальных продавцов.' }}</p></header>
<div class="bimm-catalog">
    <aside class="bimm-sidebar"><h3>В этой категории</h3><div class="bimm-sidebar__categories">@foreach($categories as $subcategory)<a href="{{ $regionalUrl->route('catalog.show', ['alias' => $subcategory->alias]) }}"><span>{{ $subcategory->name }}</span><span>{{ $categoryProductsCounts[$subcategory->id] ?? 0 }}</span></a>@endforeach</div></aside>
    <section>
        <div class="bimm-catalog__toolbar"><span class="bimm-toolbar-count">{{ $products->total() }} товаров</span><div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">@include('components.catalog-region-selector') @include('components.catalog-sort-dropdown')</div></div>
        @if($products->isNotEmpty())
            <div class="bimm-product-grid">@foreach($products as $product) @include('components.home-product-card', ['product' => $product]) @endforeach</div>
            @if($products->hasPages())<div class="bimm-pagination">{{ $products->links('vendor.pagination.custom') }}</div>@endif
        @else
            <div class="bimm-empty"><p>В этой категории пока нет опубликованных товаров.</p><a class="bimm-button bimm-button--dark" href="{{ $regionalUrl->route('catalog.index') }}">Вернуться в каталог</a></div>
        @endif
    </section>
</div>
@endsection
