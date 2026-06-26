@extends('main_layots')
@section('title', 'Избранные товары | Маркетплейс ПМР')

@section('content')

<div class="container container-240 favorites-page">
    <div id="catalog-top"></div>
    <div class="e-product">
        <ul class="breadcrumb v4">
            <li><a href="{{ $regionalUrl->route('home') }}">Главная</a></li>
            <li class="active">Избранное</li>
        </ul>

        <section class="favorites-hero" aria-labelledby="favorites-title">
            <div>
                <h1 id="favorites-title">Избранные товары</h1>
            </div>
            <div class="favorites-hero__meta" id="favorites-count-wrapper" style="display:none;">
                <span id="favorites-count-text"></span>
            </div>
        </section>

        {{-- Состояние загрузки --}}
        <div id="favorites-loading" class="favorites-loading" aria-live="polite">
            <i class="ion-load-c" aria-hidden="true"></i>
            <span>Загружаем избранное</span>
        </div>

        {{-- Сетка товаров (скрыта, пока не загрузятся) --}}
        <div id="favorites-grid" style="display:none;">
            <div class="product-collection-grid product-grid catalog-products-grid favorites-products-grid" id="favorites-products-row"></div>
        </div>

        {{-- Кнопка "Загрузить ещё" --}}
        <div class="favorites-load-more" id="favorites-load-more-wrapper" style="display:none;">
            <button class="home-btn home-btn--light favorites-load-more__button" id="favorites-load-more-btn"
                    data-current-page="1"
                    data-last-page="1">
                <i class="ion-ios-reload"></i> Загрузить ещё
            </button>
        </div>

        {{-- Пустое состояние --}}
        <div class="favorites-empty" id="favorites-empty" style="display:none;">
            <section class="favorites-empty__card" aria-labelledby="favorites-empty-title">
                <span class="favorites-empty__icon" aria-hidden="true">
                    <i class="fa-regular fa-heart"></i>
                </span>
                <div>
                    <h2 id="favorites-empty-title">В избранном пока пусто</h2>
                    <p>Нажимайте сердечко на карточках товаров — сохранённые позиции появятся здесь.</p>
                    <div class="favorites-empty__actions">
                        <a href="{{ $regionalUrl->route('catalog.index') }}" class="home-btn home-btn--primary">Перейти в каталог</a>
                        <a href="{{ $regionalUrl->route('catalog.sale') }}" class="home-btn home-btn--light">Посмотреть скидки</a>
                    </div>
                </div>
            </section>

            @if($recommendedProducts->isNotEmpty())
                <section class="home-section favorites-recommendations" aria-labelledby="favorites-recommendations-title">
                    <div class="home-section__heading">
                        <div>
                            <h2 id="favorites-recommendations-title">Можно начать с этого</h2>
                            <p>Подобрали актуальные товары, которые есть в продаже сейчас.</p>
                        </div>
                        <a href="{{ $regionalUrl->route('catalog.index') }}">Все товары</a>
                    </div>
                    <div class="home-products favorites-products-grid">
                        @foreach($recommendedProducts as $product)
                            @include('components.home-product-card', ['product' => $product])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    var currentPage = 1;
    var lastPage    = 1;
    var allIds      = [];
    var requestToken = 0;

    var $loading   = $('#favorites-loading');
    var $grid      = $('#favorites-grid');
    var $row       = $('#favorites-products-row');
    var $empty     = $('#favorites-empty');
    var $countWrap = $('#favorites-count-wrapper');
    var $countText = $('#favorites-count-text');
    var $loadMoreWrap = $('#favorites-load-more-wrapper');
    var $loadMoreBtn  = $('#favorites-load-more-btn');

    function readFavorites() {
        if (typeof getFavorites === 'function') {
            return getFavorites();
        }

        try {
            var parsed = JSON.parse(localStorage.getItem('favorites') || '[]');
            if (!Array.isArray(parsed)) return [];
            return parsed
                .map(function(id) { return parseInt(id, 10); })
                .filter(function(id, index, ids) {
                    return Number.isInteger(id) && id > 0 && ids.indexOf(id) === index;
                });
        } catch(e) { return []; }
    }

    function showEmptyState() {
        $loading.hide();
        $grid.hide();
        $row.empty();
        $countWrap.hide();
        $loadMoreWrap.hide();
        $empty.show();
    }

    function updateCount(total) {
        if (total > 0) {
            $countText.text('Сохранено ' + total + ' ' + pluralForm(total, 'товар', 'товара', 'товаров'));
            $countWrap.show();
        } else {
            $countWrap.hide();
        }
    }

    function showProductsState() {
        $loading.hide();
        $empty.hide();
        $grid.show();
    }

    function displayedProductsCount() {
        return $row.children('.home-product').length;
    }

    function loadFavorites() {
        var favorites = readFavorites();
        requestToken += 1;
        var token = requestToken;

        currentPage = 1;
        lastPage = 1;
        allIds = favorites;
        $loadMoreWrap.hide();

        if (!favorites || favorites.length === 0) {
            showEmptyState();
            return;
        }

        $empty.hide();
        $grid.hide();
        $row.empty();
        $countWrap.hide();
        $loading.show();

        $.ajax({
            url: '{{ $regionalUrl->route("favorites.products", [], false) }}',
            type: 'GET',
            dataType: 'json',
            data: { ids: allIds.join(',') },
            success: function(response) {
                if (token !== requestToken) return;

                $loading.hide();

                if (!response || !response.html || $.trim(response.html) === '') {
                    showEmptyState();
                    return;
                }

                $row.html(response.html);
                showProductsState();

                lastPage    = response.last_page || 1;
                currentPage = 1;

                updateCount(response.total || 0);

                if (response.has_more) {
                    $loadMoreBtn
                        .data('current-page', 1)
                        .data('last-page', lastPage);
                    $loadMoreWrap.show();
                }

                if (typeof initWishlists === 'function') initWishlists();
            },
            error: function() {
                if (token !== requestToken) return;
                showEmptyState();
            }
        });
    }

    // Кнопка "Загрузить ещё"
    $loadMoreBtn.on('click', function() {
        var btn           = $(this);
        var curPage       = parseInt(btn.data('current-page'));
        var maxPage       = parseInt(btn.data('last-page'));
        var nextPage      = curPage + 1;

        btn.prop('disabled', true).addClass('loading');
        btn.find('i').css('animation-play-state', 'running');

        $.ajax({
            url: '{{ $regionalUrl->route("favorites.loadMore", [], false) }}',
            type: 'GET',
            data: {
                ids:  allIds.join(','),
                page: nextPage
            },
            success: function(html) {
                $row.append(html);

                if (typeof initWishlists === 'function') initWishlists();
                $(document).trigger('cards:loaded');

                btn.data('current-page', nextPage);

                if (nextPage >= maxPage) {
                    $loadMoreWrap.fadeOut();
                } else {
                    btn.prop('disabled', false).removeClass('loading');
                    btn.find('i').css('animation-play-state', 'paused');
                }
            },
            error: function() {
                btn.prop('disabled', false).removeClass('loading');
                btn.find('i').css('animation-play-state', 'paused');
            }
        });
    });

    $(document).on('wishlist:changed', function(event, favorites, productId, added) {
        if (added) {
            var $sourceCard = $('.wishlist-btn[data-product-id="' + productId + '"]').closest('.home-product').first();

            if ($sourceCard.length && $row.find('.wishlist-btn[data-product-id="' + productId + '"]').length === 0) {
                var $card = $sourceCard.clone(false, false);
                $card.find('.icon-wishlist').addClass('wishlist-active');
                $row.prepend($card);
                showProductsState();
            } else if (!$sourceCard.length) {
                loadFavorites();
                return;
            }
        } else {
            $row.find('.wishlist-btn[data-product-id="' + productId + '"]').closest('.home-product').remove();

            if (displayedProductsCount() === 0) {
                showEmptyState();
            }
        }

        updateCount(displayedProductsCount());
        if (typeof initWishlists === 'function') initWishlists();
    });

    function pluralForm(n, one, few, many) {
        var mod10 = n % 10, mod100 = n % 100;
        if (mod10 === 1 && mod100 !== 11) return one;
        if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return few;
        return many;
    }

    loadFavorites();
});
</script>
@endpush

@endsection
