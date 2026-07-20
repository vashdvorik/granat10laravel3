@extends('main_layots')

@section('title', 'Каталог товаров — BIMM')

@section('content')
<nav class="bimm-breadcrumbs" aria-label="Хлебные крошки"><a href="{{ $regionalUrl->route('home') }}">Главная</a><span>Каталог</span></nav>
<header class="bimm-page-head"><span class="bimm-kicker">Открытый каталог</span><h1>Товары рядом</h1><p>Выбирайте предложения локальных продавцов. Фильтруйте по категории, региону и цене.</p></header>

<div class="bimm-catalog">
    <aside class="bimm-sidebar">
        <h3>Категории</h3>
        <div class="bimm-sidebar__categories">
            @foreach($categories as $subcategory)
                <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $subcategory->alias]) }}"><span>{{ $subcategory->name }}</span><span>{{ $categoryProductsCounts[$subcategory->id] ?? 0 }}</span></a>
            @endforeach
        </div>
    </aside>
    <section>
        <div class="bimm-catalog__toolbar">
            <span class="bimm-toolbar-count">{{ $products->total() }} товаров</span>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                @include('components.catalog-region-selector')
                @include('components.catalog-sort-dropdown')
            </div>
        </div>
        @if($products->isNotEmpty())
            <div class="bimm-product-grid">@foreach($products as $product) @include('components.home-product-card', ['product' => $product]) @endforeach</div>
            @if($products->hasPages())
                <div class="bimm-pagination">{{ $products->links('vendor.pagination.custom') }}</div>
                @if($products->currentPage() < $products->lastPage())
                    <div style="margin-top:18px;text-align:center"><button class="bimm-button bimm-button--light" type="button" data-bimm-load-more data-url="{{ $regionalUrl->route('catalog.loadMore') }}" data-current-page="{{ $products->currentPage() }}" data-last-page="{{ $products->lastPage() }}">Показать ещё</button></div>
                @endif
            @endif
        @else
            <div class="bimm-empty"><i class="fa-regular fa-face-frown" style="font-size:30px;margin-bottom:12px"></i><p>По выбранным условиям товаров пока нет.</p><a class="bimm-button bimm-button--dark" href="{{ $regionalUrl->route('catalog.index') }}">Сбросить фильтры</a></div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
document.querySelector('[data-bimm-load-more]')?.addEventListener('click', function () {
    var button = this, page = Number(button.dataset.currentPage) + 1;
    button.disabled = true;
    fetch(button.dataset.url + '?page=' + page).then(function (response) { return response.text(); }).then(function (html) {
        document.querySelector('.bimm-product-grid').insertAdjacentHTML('beforeend', html);
        button.dataset.currentPage = page;
        button.disabled = page >= Number(button.dataset.lastPage);
        if (button.disabled) button.remove();
    });
});
</script>
@endpush
