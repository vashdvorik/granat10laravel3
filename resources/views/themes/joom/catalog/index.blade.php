@extends('main_layots')

@section('title', 'Каталог товаров — BIMM')

@section('content')
<nav class="joom-breadcrumbs" aria-label="Хлебные крошки"><a href="{{ $regionalUrl->route('home') }}">Главная</a><span>Каталог</span></nav>
<header class="joom-page-head"><span class="joom-eyebrow">ВЫБИРАЙ ЛЕГКО</span><h1>{{ $seoHeading ?? 'Каталог товаров' }}</h1><p>Предложения местных продавцов с понятной ценой, доставкой и прямым контактом.</p></header>

<div class="joom-catalog-layout">
    <aside class="joom-sidebar">
        <div class="joom-sidebar__title"><strong>Категории</strong><a href="{{ $regionalUrl->route('catalog.index') }}">Все</a></div>
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
            @if($products->hasPages() && $products->currentPage() < $products->lastPage())<div class="joom-load-more"><button class="joom-button joom-button--quiet" type="button" data-joom-load-more data-url="{{ $regionalUrl->route('catalog.loadMore') }}" data-current-page="{{ $products->currentPage() }}" data-last-page="{{ $products->lastPage() }}">Показать ещё</button></div>@endif
        @else
            <div class="joom-empty"><i class="fa-regular fa-face-frown"></i><h2>Товаров не нашлось</h2><p>Попробуйте изменить регион, категорию или сортировку.</p><a class="joom-button joom-button--primary" href="{{ $regionalUrl->route('catalog.index') }}">Сбросить фильтры</a></div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
document.querySelector('[data-joom-load-more]')?.addEventListener('click', function () {
    var button = this, page = Number(button.dataset.currentPage) + 1;
    button.disabled = true;
    fetch(button.dataset.url + '?page=' + page).then(function (response) { return response.text(); }).then(function (html) {
        document.querySelector('.joom-catalog-grid').insertAdjacentHTML('beforeend', html);
        button.dataset.currentPage = page;
        button.disabled = page >= Number(button.dataset.lastPage);
        if (button.disabled) button.remove();
    });
});
</script>
@endpush
