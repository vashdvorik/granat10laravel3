<!DOCTYPE html>
<html lang="ru">
<!-- подключаем head -->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>{{ $seoTitle ?: (trim($__env->yieldContent('title')) ?: \App\Support\PageTitle::brand()) }}</title>
    @include('partials.seo.head')
    <link rel="stylesheet" href="@assetv('css/owl.carousel.min.css')">
    <link rel="shortcut icon" href="{{ asset('img/favicon.png') }}?v={{ filemtime(public_path('img/favicon.png')) }}" type="image/png">
    <link rel="stylesheet" href="@assetv('css/slick.css')">
    <link rel="stylesheet" href="@assetv('css/slick-theme.css')">
    <link rel="stylesheet" href="@assetv('css/owl.theme.default.min.css')">
    <link rel="stylesheet" href="@assetv('css/cabinet.css')"> 
    <link rel="stylesheet" type="text/css" href="@assetv('css/style.css')">
    <link rel="stylesheet" type="text/css" href="@assetv('css/bootstrap-slider.css')">
    <link rel="stylesheet" type="text/css" href="@assetv('css/pagination.css')">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Якорь пагинации - отступ от header */
        #catalog-top {
            scroll-margin-top: 120px;
            display: block;
        }
        
        .icon-shop {
        background-image: url("{{ asset('img/icon-ship.png') }}");
        background-repeat: no-repeat;
        height: 20px;
        width: 20px;
        background-size: contain;
    }
    @media (max-width: 767px) {

        /* баннер отступает вниз*/
        .pd-banner {
            margin-top: 20px !important; 
        }
        /* Скрываем хлебные крошки */
        .breadcrumb, 
        .breadcrumbs, 
        [aria-label="breadcrumb"] {
            display: none !important;
        }

        /* Скрываем слово каталог и надпись*/
        .pd-top {
                display: none !important;
            }
        /* Отступы для сетки товаров */
        .product-collection-grid {
            padding-left: 1px !important;
            padding-right: 1px !important;
        }
    
        /* СКРЫВАЕМ ПЕРЕКЛЮЧАТЕЛЬ ВИДА (GRID/LIST) */
        .view-mode {
            display: none !important;
        }
        /* Основная структура */
        .product-item.col-xs-6 {
            width: 50% !important;
            float: left !important;
            padding-left: 5px !important;
            padding-right: 5px !important;
            margin-bottom: 10px !important;
        }
        
        .product-collection-grid .row {
            margin-left: -5px !important;
            margin-right: -5px !important;
        }
        
        /* Внутренний контейнер */
        .product-item .product-inner {
            margin-bottom: 0 !important;
            overflow: hidden !important;
        }
        
        /* Внутренние отступы карточки */
        .product-item .pd-bd {
            padding: 5px !important;
            overflow: hidden !important;
        }
        
        /* Изображение товара - скругление верхних углов */
        .product-item .product-img {
            margin-bottom: 5px !important;
            padding: 0 !important;
            overflow: hidden !important;
            position: relative !important;
        }
        
        .product-item .product-img img {
            border-radius: 8px 8px 0 0 !important;
        }
        
        /* Лента со скидкой - УМЕНЬШЕННАЯ И СМЕЩЕННАЯ ВНУТРЬ */
        .product-item .ribbon-price {
            font-size: 8px !important;
            padding: 0 !important;
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            min-height: 32px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            line-height: 1 !important;
            top: 5px !important;
            right: 5px !important;
            left: auto !important;
        }
        
        .product-item .ribbon-price span {
            font-size: 8px !important;
            font-weight: bold !important;
        }
        
        /* Блок информации */
        .product-item .product-info {
            padding: 5px !important;
            overflow: hidden !important;
        }
        
        /* Скрываем группу цветов */
        .product-item .color-group {
            display: none !important;
        }
        
        /* Скрываем список характеристик */
        .product-item .element-list-left,
        .product-item .desc-list {
            display: none !important;
        }
        
        /* Контейнер основного содержимого */
        .product-item .element-list-middle {
            padding: 0 !important;
            margin: 0 !important;
        }
        
        /* Скрываем рейтинг */
        .product-item .product-rating,
        .product-item .bd-rating {
            display: none !important;
        }
        
        /* Название магазина */
        .product-item .product-cate {
            font-size: 10px !important;
            margin-bottom: 3px !important;
            line-height: 1.2 !important;
        }
        
        /* Заголовок товара */
        .product-item .product-title {
            font-size: 12px !important;
            line-height: 1.2 !important;
            margin-bottom: 5px !important;
        }
        
        .product-item .product-title a {
            font-size: 12px !important;
        }
        
        /* Блок с ценой и кнопкой просмотра */
        .product-item .product-bottom {
            margin-top: 5px !important;
            margin-bottom: 5px !important;
            padding: 0 !important;
        }
        
        /* Цена */
        .product-item .product-price {
            font-size: 13px !important;
            margin-bottom: 0 !important;
        }
        
        .product-item .product-price span {
            font-size: 13px !important;
        }
        
        .product-item .product-price .old {
            font-size: 11px !important;
        }
        
        /* Кнопка просмотра */
        .product-item .btn-view {
            width: 25px !important;
            height: 25px !important;
            padding: 2px !important;
        }
        
        .product-item .btn-view .icon-bg {
            width: 16px !important;
            height: 16px !important;
            background-size: contain !important;
        }
        
        /* Скрываем product-bottom-group */
        .product-item .product-bottom-group {
            display: none !important;
        }
        
        /* Раскидываем иконки по всей ширине карточки */
        .product-item .product-button-group {
            margin: 5px 0 0 0 !important;
            padding: 0 !important;
            display: flex !important;
            justify-content: space-between !important;
            gap: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }

        .product-item .product-button-group .btn-icon {
            width: 26px !important;
            height: 26px !important;
            padding: 3px !important;
            margin: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
        }

        .product-item .product-button-group .icon-bg {
            width: 14px !important;
            height: 14px !important;
            background-size: contain !important;
        }
    }

@media (max-width: 1024px) {
    .mobile-categories-wrapper {
        display: block !important;
        margin: 15px 0;
    }
    
    /* Категории не занимают весь экран: два ряда, остальное листается вправо.
       Карточка чуть уже трети ширины, поэтому справа выглядывает следующая и видно, что список продолжается. */
    .mobile-categories-grid {
        display: grid;
        grid-auto-flow: column;
        grid-template-rows: repeat(2, auto);
        grid-auto-columns: calc((100% - 20px) / 3.3);
        gap: 10px;
        /* до краёв экрана; отступ внутри оставляет место тени карточек, которую иначе обрезала бы прокрутка */
        margin: 0 -15px;
        padding: 4px 15px 10px;
        overflow-x: auto;
        overscroll-behavior-x: contain;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }

    .mobile-categories-grid::-webkit-scrollbar {
        display: none;
    }

    /* Главная страница каталога (корневые категории): карточки по три в ряд, ряды переносятся, вбок не листаются */
    .mobile-categories-grid.mobile-categories-grid--wrap {
        grid-auto-flow: row;
        grid-template-rows: none;
        grid-template-columns: repeat(3, 1fr);
        grid-auto-columns: auto;
        margin: 0;
        padding: 0;
        overflow: visible;
    }

    /* Совсем мало категорий: один ряд на всю ширину, листать нечего */
    .mobile-categories-grid.mobile-categories-grid--single {
        grid-template-rows: auto;
        grid-auto-columns: calc((100% - 20px) / 3);
        margin: 0;
        padding: 0;
        overflow: visible;
    }
    
    .mobile-category-card {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .mobile-category-card:active {
        transform: scale(0.95);
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }
    
    .mobile-category-card a {
        display: block;
        text-decoration: none;
        color: #333;
    }
    
    .mobile-category-image {
        width: 100%;
        padding-top: 100%;
        position: relative;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .mobile-category-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .mobile-category-image i {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 32px;
        color: #fff;
    }
    
    .mobile-category-name {
        padding: 8px 6px;
        font-size: 11px;
        line-height: 1.3;
        text-align: center;
        font-weight: 500;
        min-height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Кнопка назад */
    .mobile-category-card.back-card .mobile-category-image {
        background: linear-gradient(135deg, #54f0ff 0%, #c26af5 100%);
    }
    
    .mobile-category-card.back-card .mobile-category-name {
        font-weight: 600;
    }

/* Стили для текстовых плиток (без иконок) */

/* Текстовые плитки: не больше двух рядов, остальное листается вправо; «Назад» первая плитка первого ряда */
.mobile-categories-list {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    /* до краёв экрана; отступ внутри оставляет место тени плиток, которую иначе обрезала бы прокрутка */
    margin: 11px -15px;
    padding: 4px 15px 10px;
    overflow-x: auto;
    overscroll-behavior-x: contain;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}

.mobile-categories-list::-webkit-scrollbar {
    display: none;
}

.mobile-categories-rows {
    display: flex;
    flex: 0 0 auto;
    flex-direction: column;
    gap: 8px;
    width: max-content;
}

.mobile-categories-row {
    display: flex;
    gap: 8px;
}

.mobile-category-tile {
    flex: 0 0 auto;
    white-space: nowrap;
    min-width: 50px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    transition: all 0.2s;
}

.mobile-category-tile:active {
    transform: scale(0.98);
    box-shadow: 0 1px 2px rgba(0,0,0,0.12);
}

.mobile-category-tile a {
    display: block;
    padding: 12px 16px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
    font-weight: 500;
}

/* Кнопка "Назад" для плиток */
.mobile-category-tile.back-tile {
    background: linear-gradient(135deg, #e8f4f8 0%, #e8e4f3 100%);
    /* рамка внутренней тенью, а не border: иначе плитка выше остальных на 2 px */
    box-shadow: 0 2px 4px rgba(0,0,0,0.08), inset 0 0 0 1px #d1d1d1;
}

.mobile-category-tile.back-tile a {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: #555;
}

.mobile-category-tile.back-tile i {
    font-size: 16px;
}

/* Стили для кнопки "Загрузить еще" на мобильных */
.load-more-wrapper {
    padding: 20px 0;
    margin-top: 15px;
}

.btn-load-more {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    padding: 12px 30px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-load-more:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-load-more:disabled {
    background: #ccc;
    cursor: not-allowed;
    box-shadow: none;
}

.btn-load-more i {
    margin-right: 5px;
    animation: rotate 1s linear infinite;
    animation-play-state: paused;
}

.btn-load-more.loading i {
    animation-play-state: running;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
}

    /* Избранное — активное сердечко */
    .icon-wishlist.wishlist-active {
        filter: brightness(0) saturate(100%) invert(27%) sepia(51%) saturate(2878%) hue-rotate(346deg) brightness(104%) contrast(97%);
    }

    /* Иконки соцсетей в футере: FA6 не даёт display:inline-block автоматически */
    .social a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .regional-selector .dropdown-menu {
        max-height: 420px;
        min-width: 280px;
        overflow-y: auto;
        padding: 8px 0;
    }

    .regional-selector__group {
        padding: 8px 16px 4px;
        color: #777;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .regional-selector__option {
        width: 100%;
        border: 0;
        background: transparent;
        padding: 7px 16px;
        text-align: left;
    }

    .regional-selector__option:hover,
    .regional-selector__option.is-active {
        background: #f5f5f5;
        color: #e53935;
    }

    </style>

    
    {{-- SEO: Pagination meta tags --}}
    @yield('meta')
    <link rel="stylesheet" href="@assetv('css/marketplace-design.css')">
    @stack('head')
    @stack('styles')
</head>

<body>

{{-- Выезжающее меню на телефоне. Плоский список без карточек: логотип и закрытие, одна кнопка для продавцов, категории
     (раскрываются по стрелке), три раздела сайта и ссылки помощи. Поиска и выбора города здесь нет: поиск есть на главной
     и в нижней панели, выбор города стоит отдельной строкой под шапкой. Раскрытие категорий делает public/js/main.js
     (.js-menubar .icon-sub-menu, .js-open-menu), закрытие — .js-close. --}}
<div class="pushmenu menu-home5">
    <div class="menu-push drawer">
        <div class="drawer__head">
            <a href="{{ $regionalUrl->route('home') }}" class="drawer__logo" aria-label="BIMM — на главную">
                <img src="{{ asset('img/logo.png') }}" alt="BIMM">
            </a>
            <button type="button" class="close-left js-close" aria-label="Закрыть меню">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <div class="drawer__account">
            @auth
                <a class="drawer__cta" href="{{ route('account.index') }}">Мой кабинет</a>
            @else
                <a class="drawer__cta" href="{{ route('register') }}">Стать продавцом</a>
                <p class="drawer__login">Уже продаёте? <a href="{{ route('login') }}">Войти</a></p>
            @endauth
        </div>

        <div class="drawer__label">Категории</div>
        {{-- Если выбран город, об этом сказано прямо над списком: список категорий, поиск и каталог показывают только его.
             Если города нет (Все регионы), блока нет. --}}
        @if(config('regionality.enabled') && $regionalContext->settlement())
            <div class="drawer__region" role="status">
                <span class="drawer__region-text">
                    <i class="fa fa-map-marker" aria-hidden="true"></i>
                    Товары и поиск только {{ $regionalContext->settlement()->inPhrase() }}
                </span>
                <form method="POST" action="{{ route('regionality.update') }}">
                    @csrf
                    <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                    <button type="submit" class="drawer__region-reset">Показать все регионы</button>
                </form>
            </div>
        @endif
        {{-- Полное дерево категорий весит около 230 КБ HTML, поэтому на странице только верхний уровень (ссылки есть и без
             скрипта), а всё дерево public/js/drawer-menu.js подгружает при касании кнопки меню (MenuController). --}}
        <ul class="nav-home5 js-menubar" data-menu-url="{{ $regionalUrl->route('menu.categories') }}">
            @include('components.menu-tree', ['items' => $menuRootCategories, 'level' => 1, 'shallow' => true])
        </ul>

        <nav class="drawer__links" aria-label="Разделы сайта">
            <a href="{{ $regionalUrl->route('shops') }}"><i class="fa fa-shopping-bag" aria-hidden="true"></i><span>Магазины</span></a>
            <a href="{{ $regionalUrl->route('catalog.sale') }}"><i class="fa fa-percent" aria-hidden="true"></i><span>Распродажи</span></a>
            <a href="{{ $regionalUrl->route('favorites') }}"><i class="fa fa-heart" aria-hidden="true"></i><span>Избранное</span></a>
        </nav>

        <div class="drawer__help">
            <a href="{{ route('seller.help') }}">Как продавать на BIMM</a>
            <a href="{{ route('help') }}">Помощь</a>
        </div>
    </div>
</div>
<div class="wrappage">

 <!-- подключаем шапку сайта-->
<header id="header" class="header-v1">
    <!--Баннер вверху страницы  -->
    <!--   <div class="header-top-banner>
        <a href="#"><img src="{{ asset('img/banner-top2.jpg') }}" alt="" class="img-responsive"></a>
        </div>-->


    <!-- ПЕРВАЯ ЧАСТЬ ШАПКИ САЙТА (ЛОГОТИП, ПОИСК, ИКОНКИ КОРЗИНЫ)-->
    <div class="header-center">
        <div class="container container-240">
            <div class="row flex">
                <!-- логотип -->
                <div class="col-lg-2 col-md-2 col-sm-6 col-xs-6 v-center header-logo">
                    <a href="{{ $regionalUrl->route('home') }}" aria-label="BIMM — на главную"><img src="{{ asset('img/logo.png') }}" alt="BIMM — товары Приднестровья" class="img-responsive"></a>
                </div>
    <!-- поиск по сайту -->
                <div class="col-lg-7 col-md-7 v-center header-search hidden-xs hidden-sm">
                    <form method="get" class="searchform ajax-search" action="{{ $regionalUrl->route('search') }}" role="search">
                        <input type="hidden" name="type" value="product">
                        <input type="text" name="q" class="form-control" placeholder="Я ищу ..." value="{{ request('q') }}" aria-label="Поиск товаров и магазинов" autocomplete="off">
                        <input type="hidden" name="category" id="search-category-input" value="{{ request('category') }}">
                        {{-- Справа в строке поиска стоит выбор города (раньше здесь был выбор категории). Если региональность
                             выключена или городов нет, остаётся прежний выбор категории. Категория, уже заданная в адресе
                             поиска, сохраняется скрытым полем выше. --}}
                        @php
                            $regionSwitcherAvailable = config('regionality.enabled') && $regionalContext->availableRegions()->isNotEmpty();
                        @endphp
                        <div class="search-panel {{ $regionSwitcherAvailable ? 'search-panel--region' : '' }}">
                            @if($regionSwitcherAvailable)
                                @include('components.region-switcher', ['variant' => 'header'])
                            @else
                                <a class="dropdown-toggle" data-toggle="dropdown" href='#' id="search-category-label">
                                    @if(request('category') && isset($menuRootCategories))
                                        @php $activeSearchCat = $menuRootCategories->firstWhere('alias', request('category')); @endphp
                                        {{ $activeSearchCat ? $activeSearchCat->name : 'Все категории' }}
                                    @else
                                        Все категории
                                    @endif
                                    <span class="fa fa-caret-down"></span>
                                </a>
                                <ul id="category" class="dropdown-menu dropdown-category">
                                    <li><a href="#" data-alias="" data-name="Все категории">Все категории</a></li>
                                    @foreach($menuRootCategories as $cat)
                                        <li><a href="#" data-alias="{{ $cat->alias }}" data-name="{{ $cat->name }}">{{ $cat->name }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <span class="input-group-btn">
                            <button class="button_search" type="submit"><i class="ion-ios-search-strong"></i></button>
                        </span>
                    </form>
                </div>
        <!-- 3 иконки кабинет, лайки, корзина -->
                <div class="col-lg-3  col-md-3 col-sm-6 col-xs-6 v-center header-sub">
                    <div class="right-panel">
                        <div class="header-sub-element hidden-xs hidden-sm">
                        </div>
                <!-- Иконки -->
                        <div class="header-sub-element row">
                            {{-- Десктоп: вход и регистрация продавца. На мобильных ниже остаются иконки избранного и заказов. --}}
                            <div class="header-auth hidden-xs hidden-sm">
                                @auth
                                    <a class="header-auth__btn header-auth__btn--primary" href="{{ route('account.index') }}">
                                        <i class="fa-regular fa-user" aria-hidden="true"></i> Мой кабинет
                                    </a>
                                @else
                                    <a class="header-auth__btn header-auth__btn--light" href="{{ route('login') }}">Войти</a>
                                    <a class="header-auth__btn header-auth__btn--primary" href="{{ route('register') }}">
                                        <span class="header-auth__full">Стать продавцом</span><span class="header-auth__short">Продавцам</span>
                                    </a>
                                @endauth
                            </div>
                            <a href="{{ $regionalUrl->route('favorites') }}"><img src="{{ asset('img/icon-heart.png') }}" alt=""></a>
                            <a href="{{ route('orders.index') }}" class="header-orders-link" title="Мои заказы" aria-label="Мои заказы">
                                <i class="fa-regular fa-clipboard" aria-hidden="true"></i>
                                @if(($customerOrdersCount ?? 0) > 0)
                                    <span class="header-orders-link__count">{{ $customerOrdersCount }}</span>
                                @endif
                            </a>
                            <a href="#" class="hidden-md hidden-lg icon-pushmenu js-push-menu">
                                <i class="fa fa-bars f-15"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ВТОРАЯ ЧАСТЬ ШАПКИ САЙТА (МЕНЮ ГОРИЗНТАЛЬНОЕ)-->
    <div class="header-bottom hidden-xs hidden-sm">
        <div class="container container-240">
            <div class="row flex lr2">
                <div class="col-lg-3 widget-verticalmenu">
                    <div class="navbar-vertical">
                        <a href="{{ $regionalUrl->route('catalog.index') }}"><button class="navbar-toggles"><span>Каталог товаров</span></button></a>
                    </div>
                </div>    
                <div class="col-lg-9 widget-left">
                    <div class="flex lr">
                        <nav class="main-menu" style="margin-left: 20px;">
                            <div class="collapse navbar-collapse" id="myNavbar">
                                <ul class="nav navbar-nav">
                                    <li class="level1"><a href="{{ $regionalUrl->route('shops') }}"><img src="{{ asset('img/icon-ship.png') }}" alt="">Магазины</a></li>
                                    <li class="level1"><a href="{{ $regionalUrl->route('favorites') }}"><img src="{{ asset('img/icon-heart3.png') }}" alt="">Понравившиеся</a></li>
                                    <li class="level1"><a href="{{ $regionalUrl->route('catalog.sale') }}"><img src="{{ asset('img/skidka-logo.png') }}" alt="">Распродажи сейчас<span class="h-ribbon h-pos e-red">sale</span></a></li>
                                    <li class="level1"><a href="{{ route('orders.index') }}"><img src="{{ asset('img/icon-orders.svg') }}" alt="">Мои заказы@if(($customerOrdersCount ?? 0) > 0)<span class="main-menu__count">{{ $customerOrdersCount }}</span>@endif</a></li>
                                </ul>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
{{-- Формы для кнопок выбора города (они привязаны к ним атрибутом form: строка поиска сама внутри формы) --}}
@include('components.region-switch-forms')

<!-- подключаем основную часть индекс-страницы -->
@yield('content')

<footer class="site-footer">
    <div class="container container-240">
        <div class="site-footer__top">
            <div class="site-footer__brand">
                <a href="{{ $regionalUrl->route('home') }}" class="site-footer__logo" aria-label="BIMM — на главную">
                    <img src="{{ asset('img/logo.png') }}" alt="BIMM — товары Приднестровья">
                </a>
                <p>BIMM собирает товары продавцов из Приднестровья в одном месте. Ищите товар, смотрите, где он есть, и связывайтесь с продавцом напрямую.</p>
                <div class="site-footer__contacts">
                    <a href="tel:+37377993603"><i class="fa fa-phone" aria-hidden="true"></i> +(373) 779 93-603</a>
                    <span><i class="fa fa-map-marker" aria-hidden="true"></i> ПМР, Тирасполь</span>
                </div>
            </div>

            <nav class="site-footer__nav" aria-label="Навигация в футере">
                <section>
                    <h2>Покупателям</h2>
                    <ul>
                        <li><a href="{{ $regionalUrl->route('catalog.index') }}">Каталог товаров</a></li>
                        <li><a href="{{ $regionalUrl->route('shops') }}">Магазины</a></li>
                        <li><a href="{{ $regionalUrl->route('catalog.sale') }}">Распродажи</a></li>
                        <li><a href="{{ $regionalUrl->route('favorites') }}">Избранное</a></li>
                    </ul>
                </section>

                <section>
                    <h2>Продавцам</h2>
                    <ul>
                        <li><a href="{{ route('register') }}">Стать продавцом</a></li>
                        <li><a href="{{ route('account.index') }}">Войти продавцу</a></li>
                        <li><a href="{{ route('seller.help') }}">Как продавать</a></li>
                        <li><a href="{{ route('help') }}">Помощь</a></li>
                    </ul>
                </section>

                <section>
                    <h2>Категории</h2>
                    <ul>
                        @foreach($categories->take(6) as $category)
                            <li><a href="{{ $regionalUrl->route('catalog.show', ['alias' => $category->alias ?? $category->id]) }}">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                </section>

                <section class="site-footer__notice">
                    <h2>Как работает сайт</h2>
                    <ul>
                        <li>Оплата проходит напрямую продавцу.</li>
                        <li>Наличие, цену и получение уточняйте у магазина.</li>
                        <li>Не ищите по чатам: смотрите товары и продавцов на BIMM.</li>
                    </ul>
                </section>
            </nav>
        </div>

        @include('partials.seo.cities')

        <div class="site-footer__bottom">
            <span>© {{ now()->year }} BIMM — товары Приднестровья в одном месте</span>
            <span class="site-footer__disclaimer">Всё, что продаётся рядом, должно быть видно.</span>
        </div>
    </div>
</footer>
@include('components.mobile-bottom-nav', ['regionalUrl' => $regionalUrl])
<!-- /// -->
</div>

    <!-- кнопка перемотки наверх страницы -->  
    <a href="#" class="btn-gradient scroll_top"><i class="ion-ios-arrow-up"></i></a>

    <!--скрипты --> 
    <script src="@assetv('js/jquery.js')"></script>
    <script src="@assetv('js/bootstrap.js')"></script>
    <script src="@assetv('js/owl.carousel.min.js')"></script>
    <script src="@assetv('js/slick.js')"></script>
    <script src="@assetv('js/countdown.js')"></script>
    <script src="@assetv('js/bootstrap-slider.min.js')"></script>
    <script src="@assetv('js/main.js')"></script>
    <script>
    // Сохранение позиции скролла для возврата с карточки товара (только DESKTOP)
    const preserveScrollPages = ['catalog.index', 'catalog.show', 'catalog.sale', 'shops'];
    const currentRoute = '{{ Route::currentRouteName() }}';
    const isDesktop = window.innerWidth >= 768;
    
    if (preserveScrollPages.includes(currentRoute) && isDesktop) {
        // Сохраняем позицию скролла перед уходом со страницы каталога
        window.addEventListener('beforeunload', function() {
            sessionStorage.setItem('scrollPosition', window.scrollY);
        });

        // Восстанавливаем позицию скролла при возврате на страницу каталога
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const hasPagination = urlParams.has('page');
            const scrollPosition = sessionStorage.getItem('scrollPosition');
            
            // Восстанавливаем только если НЕТ пагинации и ЕСТЬ сохраненная позиция
            if (scrollPosition && !hasPagination) {
                window.scrollTo(0, parseInt(scrollPosition));
                sessionStorage.removeItem('scrollPosition');
            }
        });
    }

    // === ИЗБРАННОЕ (WISHLIST) ===
    function getFavorites() {
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
    function saveFavorites(ids) {
        var normalized = Array.isArray(ids)
            ? ids
                .map(function(id) { return parseInt(id, 10); })
                .filter(function(id, index, values) {
                    return Number.isInteger(id) && id > 0 && values.indexOf(id) === index;
                })
            : [];
        localStorage.setItem('favorites', JSON.stringify(normalized));
        return normalized;
    }
    function initWishlists() {
        var favorites = getFavorites();
        $('.wishlist-btn').each(function() {
            var productId = parseInt($(this).data('product-id'));
            $(this).find('.icon-wishlist').toggleClass('wishlist-active', favorites.indexOf(productId) !== -1);
        });
    }
    $(document).ready(function() {
        initWishlists();
        $(document).on('click', '.wishlist-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var productId = parseInt($btn.data('product-id'));
            if (!Number.isInteger(productId) || productId <= 0) {
                return;
            }
            var favorites = getFavorites();
            var idx = favorites.indexOf(productId);
            if (idx === -1) {
                favorites.push(productId);
                $btn.find('.icon-wishlist').addClass('wishlist-active');
            } else {
                favorites.splice(idx, 1);
                $btn.find('.icon-wishlist').removeClass('wishlist-active');
            }
            favorites = saveFavorites(favorites);
            $(document).trigger('wishlist:changed', [favorites, productId, idx === -1]);
        });
    });

    // Выбор категории в поиске (работает, только если в шапке нет выбора города)
    $(document).on('click', '#category a', function(e) {
        e.preventDefault();
        const alias = $(this).data('alias');
        const name = $(this).data('name');
        $('#search-category-input').val(alias);
        $('#search-category-label').html(name + ' <span class="fa fa-caret-down"></span>');
    });

    // 📱 AJAX: Загрузка дополнительных товаров на мобильных
    $(document).ready(function() {
        // Проверяем, что это мобильное устройство
        if (window.innerWidth < 768) {
            $('.btn-load-more').on('click', function() {
                const btn = $(this);
                const url = btn.data('url');
                let currentPage = parseInt(btn.data('current-page'));
                const lastPage = parseInt(btn.data('last-page'));
                const seller = btn.data('seller') || '';
                const sort = btn.data('sort') || '';
                
                // Отключаем кнопку и показываем загрузку
                btn.prop('disabled', true).addClass('loading');
                btn.find('i').css('animation-play-state', 'running');
                
                // Следующая страница
                const nextPage = currentPage + 1;
                
                // Определяем контейнер для добавления элементов
                const targetSelector = btn.data('container');
                const container = targetSelector
                    ? $(targetSelector)
                    : ($('.product-collection-grid').length > 0
                        ? $('.product-collection-grid')
                        : $('.blog-grid .row'));
                
                // AJAX запрос
                $.ajax({
                    url: url,
                    type: 'GET',
                    data: {
                        page: nextPage,
                        seller: seller,
                        sort: sort
                    },
                    success: function(html) {
                        // Добавляем элементы в конец списка
                        container.append(html);
                        // Обновляем состояние сердечек у новых карточек
                        if (typeof initWishlists === 'function') initWishlists();
                        // Инициализируем поповеры телефона для новых карточек
                        $(document).trigger('cards:loaded');
                        
                        // Обновляем текущую страницу
                        btn.data('current-page', nextPage);
                        
                        // Если это была последняя страница - скрываем кнопку
                        if (nextPage >= lastPage) {
                            btn.parent().fadeOut();
                        } else {
                            // Включаем кнопку обратно
                            btn.prop('disabled', false).removeClass('loading');
                            btn.find('i').css('animation-play-state', 'paused');
                        }
                    },
                    error: function() {
                        alert('Ошибка загрузки. Попробуйте еще раз.');
                        btn.prop('disabled', false).removeClass('loading');
                        btn.find('i').css('animation-play-state', 'paused');
                    }
                });
            });
        }
    });
</script>
<script src="@assetv('js/home-feed.js')" defer></script>
<script src="@assetv('js/drawer-menu.js')" defer></script>
@stack('scripts')
</body>

</html>
