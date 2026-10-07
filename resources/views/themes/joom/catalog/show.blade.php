@extends('main_layots')

@section('title', $category->name.' — BIMM')

@section('content')
<nav class="joom-breadcrumbs" aria-label="Хлебные крошки"><a href="{{ $regionalUrl->route('home') }}">Главная</a><a href="{{ $regionalUrl->route('catalog.index') }}">Каталог</a>@foreach($breadcrumbs as $crumb)<a href="{{ $regionalUrl->route('catalog.show', ['alias' => $crumb->alias]) }}">{{ $crumb->name }}</a>@endforeach<span>{{ $category->name }}</span></nav>
<header class="joom-page-head"><span class="joom-eyebrow">КАТЕГОРИЯ</span><h1>{{ $category->name }}</h1><p>{{ $category->desc ?: 'Подборка товаров и предложений от локальных продавцов.' }}</p></header>

<div class="joom-catalog-layout">
    <aside class="joom-sidebar">
        <div class="joom-sidebar__title"><strong>В этой категории</strong><a href="{{ $regionalUrl->route('catalog.index') }}">Все</a></div>
        <div class="joom-sidebar__categories">
            @foreach($categories as $subcategory)
                <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $subcategory->alias]) }}"><span>{{ $subcategory->name }}</span><small>{{ $categoryProductsCounts[$subcategory->id] ?? 0 }}</small></a>
            @endforeach
        </div>
    </aside>
    <section class="joom-catalog-content">
        <div class="joom-toolbar"><strong>{{ $products->total() }} товаров</strong><div class="joom-toolbar__tools">@include('components.catalog-region-selector') @include('components.catalog-sort-dropdown')</div></div>
        @if($products->isNotEmpty())
            <div class="joom-catalog-grid">@foreach($products as $product) @include('components.home-product-card', ['product' => $product]) @endforeach</div>
            @if($products->hasPages())<div class="joom-pagination">{{ $products->links('vendor.pagination.custom') }}</div>@endif
        @else
            <div class="joom-empty"><i class="fa-solid fa-box-open"></i><h2>В этой категории пока пусто</h2><p>Откройте весь каталог — новые предложения появляются регулярно.</p><a class="joom-button joom-button--primary" href="{{ $regionalUrl->route('catalog.index') }}">Открыть каталог</a></div>
        @endif
    </section>
</div>
@endsection
