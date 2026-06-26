<!DOCTYPE html>
<html lang="ru">
<!-- подключаем head -->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>{{ $seoTitle ?: trim($__env->yieldContent('title')) }}</title>
    @if(config('regionality.seo_enabled'))
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta name="robots" content="{{ $robotsMeta }}">
    @if($seoDescription)
    <meta name="description" content="{{ $seoDescription }}">
    @endif
    @endif
    <link rel="stylesheet" href="{{ asset('css/owl.carousel.min.css') }}">
    <link rel="shortcut icon" href="{{ asset('img/favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/slick.css') }}">
    <link rel="stylesheet" href="{{ asset('css/slick-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/owl.theme.default.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tarif.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cabinet.css') }}"> 
    <link rel="stylesheet" type="text/css" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/bootstrap-slider.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/pagination.css') }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @if(Request::is('catalog/*') || Request::is('catalog'))
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "name": "Главная",
          "item": "{{ url('/') }}"
        },
        {
          "@type": "ListItem",
          "position": 2,
          "name": "Каталог",
          "item": "{{ $regionalUrl->route('catalog.index') }}"
        }
        @if(isset($breadcrumbs) && $breadcrumbs->count() > 0)
          @foreach($breadcrumbs as $index => $crumb)
          ,{
            "@type": "ListItem",
            "position": {{ $index + 3 }},
            "name": "{{ $crumb->name }}",
            "item": "{{ $regionalUrl->route('catalog.show', ['alias' => $crumb->alias]) }}"
          }
          @endforeach
        @endif
      ]
    }
    </script>
    @endif
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
    
    /* СКРЫВАЕМ TOPBAR */
        .topbar {
            display: none !important;
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
    
    .mobile-categories-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
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

.mobile-categories-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 15px 0;
}

.mobile-category-tile {
    flex: 0 0 auto;
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
    padding: 14px 16px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
    font-weight: 500;
}

/* Кнопка "Назад" для плиток */
.mobile-category-tile.back-tile {
    background: linear-gradient(135deg, #e8f4f8 0%, #e8e4f3 100%);
    border: 1px solid #d1d1d1;
}

.mobile-category-tile.back-tile a {
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

    {{-- Open Graph для соцсетей --}}
    <meta property="og:title" content="{{ $seoTitle ?: trim($__env->yieldContent('title')) }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="Маркетплейс ПМР">
    @if($seoDescription)
    <meta property="og:description" content="{{ $seoDescription }}">
    @endif
    @hasSection('og_image')
    <meta property="og:image" content="@yield('og_image')">
    @endif
    
    {{-- SEO: Pagination meta tags --}}
    @yield('meta')
    <link rel="stylesheet" href="{{ asset('css/marketplace-design.css') }}">
    @stack('head')
    @stack('styles')
</head>

<body>

<!-- подключаем выезжающее меню для мобильных-->
<div class="pushmenu menu-home5">
    <div class="menu-push">
        <span class="close-left js-close"><i class="icon-close f-20"></i></span>
        <div class="clearfix"></div>
        <form role="search" method="get" id="searchform" class="searchform" action="/search">
           <div>
              <label class="screen-reader-text" for="q"></label>
              <input type="text" placeholder="Я ищу ..." value="" name="q" id="q" autocomplete="off"><input type="hidden" name="type" value="product"><button type="submit" id="searchsubmit"><i class="ion-ios-search-strong"></i></button>
           </div>
        </form>
        <ul class="nav-home5 js-menubar">
            @include('components.menu-tree', ['items' => $menuRootCategories, 'level' => 1])
            <!--
            <li class="level1 active dropdown"><a href="#">Одежда и обувь</a>
                <span class="icon-sub-menu"></span>
                <div class="menu-level1 js-open-menu">   
                    <ul class="level1">
                        <li class="level2">
                            <a href="#">Одежда мужская</a>
                            <ul class="menu-level-2">
                                <li class="level3"><a href="shop_full.html" title="">Вверхняя одежда</a></li>
                                <li class="level3"><a href="shopgrid_v1.html" title="">Толстовки и регланы</a></li>
                                <li class="level3"><a href="shopgrid_v2.html" title="">Белье и плавки</a></li>
                                <li class="level3"><a href="shoplist.html" title="">Костюмы</a></li>
                            </ul>
                        </li>
                        <li class="level2">
                            <a href="#">Одежда женская</a>
                            <ul class="menu-level-2">
                                <li class="level3"><a href="cat_fullwidth.html" title="">Платья</a></li>
                                <li class="level3"><a href="cat_left_sidebar.html" title="">Блузки</a></li>
                                <li class="level3"><a href="cat_right_sidebar.html" title="">Костюмы</a></li>
                            </ul>
                        </li>
                        <li class="level2">
                            <a href="#">Обувь мужская</a>
                            <ul class="menu-level-2">
                                <li class="level3"><a href="bundle.html" title="">Туфли</a></li>
                                <li class="level3"><a href="pin_product.html" title="">Кросовки</a></li>
                                <li class="level3"><a href="360degree.html" title="">Мокасины</a></li>
                                <li class="level3"><a href="feature_video.html" title="">Ботинки</a></li>
                                <li class="level3"><a href="simple.html">Сандали</a></li>
                                <li class="level3"><a href="variable.html">Шлепанцы</a></li>
                                <li class="level3"><a href="affilate.html">Сапоги</a></li>
                                <li class="level3"><a href="grouped.html">Тапочки</a></li>
                                <li class="level3"><a href="outofstock.html">Ортопедическая</a></li>
                            </ul>
                        </li>
                        <li class="level2">
                            <a href="#">Обувь женская</a>
                            <ul class="menu-level-2">
                                <li class="level3"><a href="bundle.html" title="">Туфли</a></li>
                                <li class="level3"><a href="pin_product.html" title="">Кросовки</a></li>
                                <li class="level3"><a href="360degree.html" title="">Мокасины</a></li>
                                <li class="level3"><a href="feature_video.html" title="">Ботинки</a></li>
                                <li class="level3"><a href="simple.html">Сандали</a></li>
                                <li class="level3"><a href="variable.html">Шлепанцы</a></li>
                                <li class="level3"><a href="affilate.html">Сапоги</a></li>
                                <li class="level3"><a href="grouped.html">Тапочки</a></li>
                                <li class="level3"><a href="outofstock.html">Ортопедическая</a></li>
                            </ul>
                        </li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
            </li>
            -->
            {{-- Меню категорий (рекурсивно через components.menu-tree) --}}
            <!--<li class="level1 active dropdown"><a href="#">Mega Menu</a></li>-->
        </ul>
    </div>
</div>

<div class="wrappage">

 <!-- подключаем шапку сайта-->
<header id="header" class="header-v1">
    <!--Баннер вверху страницы  -->
    <!--   <div class="header-top-banner>
        <a href="#"><img src="img/banner-top2.jpg" alt="" class="img-reponsive"></a>
        </div>-->


    <!--ПОЛОСКА СЕРОГО ЦВЕТА  -->
    <div class="topbar">
        <div class="container container-240">
            <div class="row flex">
                <div class="col-md-6 col-sm-6 col-xs-4 flex-left">
                    <div class="topbar-left">
                        <div class="element element-store regional-selector">
                            @if(config('regionality.enabled'))
                                <a id="label1" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                    <img src="{{ asset('img/icon-map.png') }}" alt="">
                                    <span>{{ $regionalContext->label() }}</span>
                                    <span class="fa fa-caret-down"></span>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="label1">
                                    <li>
                                        <form method="POST" action="{{ route('regionality.update') }}">
                                            @csrf
                                            <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                                            <button type="submit" class="regional-selector__option {{ $regionalContext->isAllRegions() ? 'is-active' : '' }}">
                                                Все регионы
                                            </button>
                                        </form>
                                    </li>
                                    @foreach($regionalRegions as $region)
                                        <li class="regional-selector__group">{{ $region->name }}</li>
                                        @foreach($region->settlements as $settlement)
                                            <li>
                                                <form method="POST" action="{{ route('regionality.update') }}">
                                                    @csrf
                                                    <input type="hidden" name="settlement_id" value="{{ $settlement->id }}">
                                                    <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                                                    <button type="submit" class="regional-selector__option {{ $regionalContext->settlement()?->is($settlement) ? 'is-active' : '' }}">
                                                        {{ $settlement->name }}
                                                        <small>({{ $settlement->type }})</small>
                                                    </button>
                                                </form>
                                            </li>
                                        @endforeach
                                    @endforeach
                                </ul>
                            @else
                                <span>
                                    <img src="{{ asset('img/icon-map.png') }}" alt="">
                                    Все регионы
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6 col-xs-8 flex-right">
                    <div class="topbar-right">
                        <div class="element hidden-xs hidden-sm">
                            <a href="{{ route('gobuyer') }}">Помощь продавцам</a>
                        </div>
                        <div class="element hidden-xs hidden-sm">
                            <a href="{{ route('account.index') }}"><i class="fa fa-user" style="margin-right:5px;"></i><span>Войти в аккаунт</span></a>
                        </div>
                <!--    <div class="element element-currency">
                            <a id="label3" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            
                            <span>RUP</span>
                            <span class="ion-ios-arrow-down f-10 e-arrow"></span>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="label3">
                                <li><a href="#">USD</a></li>
                                <li><a href="#">RUP</a></li>
                                <li><a href="#">MDL</a></li>
                            </ul>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ПЕРВАЯ ЧАСТЬ ШАПКИ САЙТА (ЛОГОТИП, ПОИСК, ИКОНКИ КОРЗИНЫ)-->
    <div class="header-center">
        <div class="container container-240">
            <div class="row flex">
                <!-- логотип -->
                <div class="col-lg-2 col-md-2 col-sm-6 col-xs-6 v-center header-logo">
                    <a href="{{ $regionalUrl->route('home') }}"><img src="{{ asset('img/logo.png') }}" alt="" class="img-reponsive"></a>
                </div>
    <!-- поиск по сайту -->
                <div class="col-lg-7 col-md-7 v-center header-search hidden-xs hidden-sm">
                    <form method="get" class="searchform ajax-search" action="{{ $regionalUrl->route('search') }}" role="search">
                        <input type="hidden" name="type" value="product">
                        <input type="text" onblur="if (this.value=='') this.value = this.defaultValue" onfocus="if (this.value==this.defaultValue) this.value = ''" name="q" class="form-control" placeholder="Я ищу ..." value="{{ request('q') }}">
                        <input type="hidden" name="category" id="search-category-input" value="{{ request('category') }}">
                        <div class="search-panel">
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
                        </div>
                        <span class="input-group-btn">
                            <button class="button_search" type="submit"><i class="ion-ios-search-strong"></i></button>
                        </span>
                    </form>
                    <div class="tags">
                        <span>Часто ищут:</span>
                        <a href="/search?q=платье">платье</a>
                        <a href="/search?q=телефон">телефон</a>
                        <a href="/search?q=наушники">наушники</a>
                        <a href="/search?q=обувь">обувь</a>
                        <a href="/search?q=куртка">куртка</a>
                        <a href="/search?q=сумка">сумка</a>
                        <a href="/search?q=ноутбук">ноутбук</a>
                        <a href="/search?q=мебель">мебель</a>
                        <a href="/search?q=часы">часы</a>
                    </div>
                </div>
        <!-- 3 иконки кабинет, лайки, корзина -->
                <div class="col-lg-3  col-md-3 col-sm-6 col-xs-6 v-center header-sub">
                    <div class="right-panel">
                        <div class="header-sub-element hidden-xs hidden-sm">
                        </div>
                <!-- Иконки -->
                        <div class="header-sub-element row">
                            <a class="hidden-xs hidden-sm" href="{{ route('account.index') }}"><img src="{{ asset('img/icon-user.png') }}" alt=""></a>
                            <a href="{{ $regionalUrl->route('favorites') }}"><img src="{{ asset('img/icon-heart.png') }}" alt=""></a>
                            
                            <div class="cart">
                                
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" id="label5">
                                    <img src="{{ asset('img/icon-cart.png') }}" alt="">
                                    
                                </a>
                                <div class="dropdown-menu dropdown-cart">
                                    <div class="bottom-cart">
                                        <div class="cart-price">
                                            <span>Чтобы оформить заказ, свяжитесь с продавцом по телефону.</span>
                                        </div>
                                        <div class="button-cart">
                                            <a href="{{ $regionalUrl->route('shops') }}" class="cart-btn btn-viewcart">Магазины</a>
                                            <a href="{{ $regionalUrl->route('catalog.index') }}" class="cart-btn e-checkout btn-gradient">Каталог</a>
                                        </div>
                                    </div>
                                </div> 
                            </div> 
                            
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
                                </ul>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- подключаем основную часть индекс-страницы -->
@yield('content')

<!-- подключаем footer  -->
 <footer>
    <div class="f-top">
        <div class="container container-240">
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                    <div class="footer-block footer-about">
                        <div class="f-logo">
                            <a href="#"><img src="{{ asset('img/logo.png') }}" alt="" class="img-reponsive"></a>
                        </div>
                        <ul class="footer-block-content">
                            <li class="address">
                                <span>Приднестровье. г Тирасполь</span>
                            </li>
                            <li class="phone">
                                <span>+(373) 779 93-603</span>
                            </li>
                            <li class="email">
                                <span>Contact@yourcompany.com</span>
                            </li>
                            <li class="time">
                                <span>Пон-Пятн 9:00 - 15:00 </span>
                            </li>
                        </ul>
                        <div class="footer-social social">
                            <h3 class="footer-block-title">Наши социальные сети</h3>
                            <a href="#"><i class="fab fa-tiktok"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-3 col-lg-3">
                    <div class="footer-block">
                        <h3 class="footer-block-title">Категории</h3>
                        <ul class="footer-block-content">
                            @foreach($categories->take(7) as $category)
                            <li><a href="{{ $regionalUrl->route('catalog.show', ['alias' => $category->alias ?? $category->id]) }}">{{ $category->name }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-2 col-lg-2">
                    <div class="footer-block">
                        <h3 class="footer-block-title">Кабинет продавца</h3>
                        <ul class="footer-block-content">
                            <li><a href="{{ route('account.index') }}">Войти в аккаунт</a></li>
                            <li><a href="{{ route('register') }}">Регистрация</a></li>
                            <li><a href="{{ route('gobuyer') }}">Помощь</a></li>

                        </ul>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                    <div class="footer-block">
                        <div class="footer-block-phone">
                            <h3 class="footer-block-title">Рекламное агентство «Гранат»</h3>
                            <p class="phone-desc">Команда профессионалов, специализирующихся на разработке эффективных рекламных IT-решений для роста и развития бизнеса в ПМР. Мы объединяем опыт в сфере маркетинга, дизайна и современных технологий, чтобы создавать инструменты, которые действительно работают.</p>
                            <p class="phone-light">+(373) 779 93-603</p>
                        </div>
                        <div class="footer-block-newsletter">
                            <h3 class="footer-block-title">Стать продавцом</h3>
                            <p>Начни продавать прямо сейчас — зарегистрируйся всего за один шаг, указав свой email. Быстро, просто и бесплатно!</p>
                {{--        <form class="form_newsletter" action="#" method="post">
                                <input type="email" value="" placeholder="Введите ваш email" name="EMAIL" id="mail" class="newsletter-input form-control">
                                <button id="subscribe" class="button_mini btn btn-gradient" type="submit">
                                    Регистрация
                                </button>
                            </form> --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="f-bottom">
        <div class="container container-240">
            <div class="row flex lr">
                <div class="col-xs-6 f-copyright"><span>@2024. Рекламное агенство "ГРАНАТ"</span></div>
            <div class="col-xs-6 f-payment hidden-xs">
            <!--    <a href="#"><img src="img/payment/mastercard.png" alt="" class="img-reponsive"></a>
                    <a href="#"><img src="img/payment/paypal.png" alt="" class="img-reponsive"></a>
                    <a href="#"><img src="img/payment/visa.png" alt="" class="img-reponsive"></a>
                    <a href="#"><img src="img/payment/american-express.png" alt="" class="img-reponsive"></a>
                    <a href="#"><img src="img/payment/western-union.png" alt="" class="img-reponsive"></a>
                    <a href="#"><img src="img/payment/jcb.png" alt="" class="img-reponsive"></a>-->
                </div> 
            </div>
        </div>
    </div>
</footer>
<!-- МОБИЛЬНОЕ МЕНЮ ВНИЗУ (Bottom Navbar) -->
<div class="mobile-bottom-navbar hidden-md hidden-lg">
    <div class="mobile-navbar-wrapper">
        <a href="{{ route('account.index') }}" class="mobile-nav-item " title="Меню">
            <i class="fa fa-user f-18"></i>
            <span class="mobile-nav-label">Кабинет</span>
        </a>
        <a href="{{ $regionalUrl->route('favorites') }}" class="mobile-nav-item" title="Избранное">
            <i class="fa fa-heart f-18"></i>
            <span class="mobile-nav-label">Избранное</span>
        </a>
        <a href="{{ $regionalUrl->route('catalog.index') }}" class="mobile-nav-item " title="Меню">
            <i class="fa fa-th f-18"></i>
            <span class="mobile-nav-label">Каталог</span>
        </a>
        <a href="{{ $regionalUrl->route('catalog.sale') }}" class="mobile-nav-item" title="Распродажи">
            <img src="{{ asset('img/skidka-logo.png') }}" alt="Распродажи" style="width:20px; height:20px;">
            <span class="mobile-nav-label">Распродажи</span>
        </a>
        <a href="{{ $regionalUrl->route('shops') }}" class="mobile-nav-item" title="Магазины">
            <img src="{{ asset('img/icon-ship.png') }}" alt="Магазины" style="width:20px; height:20px;">
            <span class="mobile-nav-label">Магазины</span>
        </a>
    </div>
</div>
<!-- /// -->
</div>

    <!-- кнопка перемотки наверх страницы -->  
    <a href="#" class="btn-gradient scroll_top"><i class="ion-ios-arrow-up"></i></a>

    <!--скрипты --> 
    <script src="{{ asset('js/jquery.js') }}"></script>
    <script src="{{ asset('js/bootstrap.js') }}"></script>
    <script src="{{ asset('js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('js/slick.js') }}"></script>
    <script src="{{ asset('js/countdown.js') }}"></script>
    <script src="{{ asset('js/bootstrap-slider.min.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>
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
        try { return JSON.parse(localStorage.getItem('favorites') || '[]'); }
        catch(e) { return []; }
    }
    function saveFavorites(ids) {
        localStorage.setItem('favorites', JSON.stringify(ids));
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
            var favorites = getFavorites();
            var idx = favorites.indexOf(productId);
            if (idx === -1) {
                favorites.push(productId);
                $btn.find('.icon-wishlist').addClass('wishlist-active');
            } else {
                favorites.splice(idx, 1);
                $btn.find('.icon-wishlist').removeClass('wishlist-active');
            }
            saveFavorites(favorites);
        });
    });

    // Выбор категории в поиске
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
@stack('scripts')
</body>

</html>
