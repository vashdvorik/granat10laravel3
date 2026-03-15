@extends('main_layots')
@section('title', 'Избранные товары | Маркетплейс ПМР')

@section('content')

<div class="container container-240">
    <div id="catalog-top"></div>
    <div class="e-product">
        <ul class="breadcrumb v4">
            <li><a href="{{ route('home') }}">Главная</a></li>
            <li class="active">Избранное</li>
        </ul>

        <div class="pd-top">
            <h1 class="title">Избранные товары</h1>
            <div class="show-element" id="favorites-count-wrapper" style="display:none;">
                <span id="favorites-count-text"></span>
            </div>
        </div>

        {{-- Состояние загрузки --}}
        <div id="favorites-loading" class="text-center" style="padding: 60px 0;">
            <i class="ion-load-c" style="font-size:40px; color:#ccc;"></i>
        </div>

        {{-- Сетка товаров (скрыта, пока не загрузятся) --}}
        <div class="product-collection-grid product-grid spc1" id="favorites-grid" style="display:none;">
            <div class="row" id="favorites-products-row">
            </div>
        </div>

        {{-- Пустое состояние --}}
        <div class="favorites-empty text-center" id="favorites-empty" style="display:none;">
            <i class="ion-ios-heart-outline favorites-empty-icon"></i>
            <p class="favorites-empty-text">Вы ещё не добавили ни одного товара в избранное.</p>
            <p style="color:#aaa;">Нажмите <i class="fa fa-heart" style="color:#e74c3c;"></i> на карточке товара, чтобы сохранить его здесь.</p>
            <a href="{{ route('catalog.index') }}" class="btn btn-primary" style="margin-top: 20px; padding: 10px 30px; border-radius: 25px;">
                Перейти в каталог
            </a>
        </div>
    </div>
</div>

<style>
    .favorites-empty {
        padding: 60px 20px;
        color: #888;
    }
    .favorites-empty-icon {
        font-size: 72px;
        color: #ddd;
        display: block;
        margin-bottom: 20px;
    }
    .favorites-empty-text {
        font-size: 18px;
        margin-bottom: 10px;
        color: #555;
    }
    #favorites-loading .ion-load-c {
        animation: fav-spin 1s linear infinite;
        display: inline-block;
    }
    @keyframes fav-spin {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }
</style>

@push('scripts')
<script>
$(document).ready(function() {
    var favorites = [];
    try {
        favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
    } catch(e) { favorites = []; }

    var $loading   = $('#favorites-loading');
    var $grid      = $('#favorites-grid');
    var $row       = $('#favorites-products-row');
    var $empty     = $('#favorites-empty');
    var $countWrap = $('#favorites-count-wrapper');
    var $countText = $('#favorites-count-text');

    if (!favorites || favorites.length === 0) {
        $loading.hide();
        $empty.show();
        return;
    }

    $.ajax({
        url: '{{ route("favorites.products") }}',
        type: 'GET',
        data: { ids: favorites.join(',') },
        success: function(html) {
            $loading.hide();
            if (!html || $.trim(html) === '') {
                $empty.show();
                return;
            }
            $row.html(html);
            $grid.show();

            var count = $row.find('.product-item').length;
            if (count > 0) {
                $countText.text('Сохранено ' + count + ' ' + pluralForm(count, 'товар', 'товара', 'товаров'));
                $countWrap.show();
            }

            // Пометить сердечки как активные
            if (typeof initWishlists === 'function') initWishlists();
        },
        error: function() {
            $loading.hide();
            $empty.show();
        }
    });

    function pluralForm(n, one, few, many) {
        var mod10 = n % 10, mod100 = n % 100;
        if (mod10 === 1 && mod100 !== 11) return one;
        if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return few;
        return many;
    }
});
</script>
@endpush

@endsection
