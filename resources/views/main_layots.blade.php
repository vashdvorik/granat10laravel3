<!DOCTYPE html>
<html lang="en">
<!-- подключаем head -->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ asset('css/owl.carousel.min.css') }}">
    <link rel="shortcut icon" href="{{ asset('img/favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/slick.css') }}">
    <link rel="stylesheet" href="{{ asset('css/slick-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/owl.theme.default.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tarif.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cabinet.css') }}"> 
    <link rel="stylesheet" type="text/css" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/bootstrap-slider.css') }}">
    <style>
        .icon-shop {
        background-image: url("{{ asset('img/icon-ship.png') }}");
        background-repeat: no-repeat;
        height: 20px;
        width: 20px;
        background-size: contain;
    }
    @media (max-width: 767px) {


        /* УМЕНЬШАЕМ ОТСТУПЫ COLLECTION-LIST */
        .collection-list,
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
    </style>
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
    <!--   <div class="header-top-banner">
        <a href="#"><img src="img/banner-top2.jpg" alt="" class="img-reponsive"></a>
        </div>-->

    <!--ПОЛОСКА СЕРОГО ЦВЕТА  -->
    <div class="topbar">
        <div class="container container-240">
            <div class="row flex">
                <div class="col-md-6 col-sm-6 col-xs-4 flex-left">
                    <div class="topbar-left">
                        <div class="element element-store hidden-xs hidden-sm">
                            <a id="label1" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            <img src="{{ asset('img/icon-map.png') }}" alt="">
                            <span>г. Тирасполь</span>
                            
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="label1">
                                <li><a href="#">Не выбрано</a></li>
                                <li><a href="#">Центр</a></li>
                                <li><a href="#">Балка</a></li>
                                <li><a href="#">Промзона</a></li>
                                <li><a href="#">Бородинка</a></li>
                                <li><a href="#">Красные казармы</a></li>
                                <li><a href="#">Западный</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6 col-xs-8 flex-right">
                    <div class="topbar-right">
                        <div class="element hidden-xs hidden-sm">
                            <a href="{{ route('gobuyer') }}">Стать продавцом</a>
                        </div>
                        <div class="element hidden-xs hidden-sm">
                            <a href="{{ route('help') }}">Помощь</a>
                        </div>
                        <div class="element hidden-xs hidden-sm">
                            <a href="#"><img src="{{ asset('img/icon-phone.png') }}" alt=""><span>Скачать приложение</span></a>
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
                    <a href="/"><img src="{{ asset('img/logo.png') }}" alt="" class="img-reponsive"></a>
                </div>
    <!-- поиск по сайту -->
                <div class="col-lg-7 col-md-7 v-center header-search hidden-xs hidden-sm">
                    <form method="get" class="searchform ajax-search" action="/search" role="search">
                        <input type="hidden" name="type" value="product">
                        <input type="text" onblur="if (this.value=='') this.value = this.defaultValue" onfocus="if (this.value==this.defaultValue) this.value = ''" name="q" class="form-control" placeholder="Я ищу ...">
                        <ul class="list-product-search hidden-xs hidden-sm">
                            <li>
                                <a class="flex align-center" href="">
                                    <div class="product-img">
                                        <img src="{{ asset('img/product/iphonex.jpg') }}" alt="">
                                    </div>
                                    <h3 class="product-title">Notebook Black Spire Smartphone Black 2.0</h3>
                                </a>
                            </li>
                            <li>
                                <a class="flex align-center" href="">
                                    <div class="product-img">
                                        <img src="{{ asset('img/product/sound.jpg') }}" alt="">
                                    </div>
                                    <h3 class="product-title">Smartphone 6S 64GB LTE</h3>
                                </a>
                            <li>
                                <a class="flex align-center" href="">
                                    <div class="product-img">
                                        <img src="{{ asset('img/product/phone4.jpg') }}" alt="">
                                    </div>
                                    <h3 class="product-title">Notebook Black Spire Smartphone Black 2.0</h3>
                                </a>
                            </li>
                            <li>
                                <a class="flex align-center" href="">
                                    <div class="product-img">
                                        <img src="{{ asset('img/product/phone5.jpg') }}" alt="">
                                    </div>
                                    <h3 class="product-title">Smartphone 6S 64GB LTE </h3>
                                </a>
                            </li>
                            <li>
                                <a class="flex align-center" href="">
                                    <div class="product-img">
                                        <img src="{{ asset('img/product/phone1.jpg') }}" alt="">
                                    </div>
                                    <h3 class="product-title">Notebook Black Spire Smartphone Black 2.0</h3>
                                </a>
                            </li>
                        </ul>
                        <div class="search-panel">
                            <a class="dropdown-toggle" data-toggle="dropdown" href='#'>Все категории <span class="fa fa-caret-down"></span></a>
                            <ul id="category" class="dropdown-menu dropdown-category">
                                <li><a href="#">Одежда и обувь</a></li>
                                <li><a href="#">Спецодежда и военторг</a></li>
                                <li><a href="#">Дом и сад</a></li>
                                <li><a href="#">Канцтовары</a></li>
                                <li><a href="#">Техника и электроника</a></li>
                                <li><a href="#">Аксесуары и украшения</a></li>
                                <li><a href="#">Подарки, хобби, книги</a></li>
                            </ul>
                        </div>
                        <span class="input-group-btn">
                            <button class="button_search" type="button"><i class="ion-ios-search-strong"></i></button>
                        </span>
                    </form>
                    <div class="tags">
                        <span>Часто ищут:</span>
                        <a href="#">женское платье</a>
                        <a href="#">тротуарная плитка</a>
                        <a href="#">беспроводные наушники</a>
                        <a href="#">фильтры для аквариума</a>
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
                            <a href="#"><img src="{{ asset('img/icon-heart.png') }}" alt=""></a>
                            <div class="cart">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" id="label5">
                                    <img src="{{ asset('img/icon-cart.png') }}" alt="">
                                    <span class="count cart-count">0</span>
                                </a>
                                <div class="dropdown-menu dropdown-cart">
                                    <ul class="mini-products-list">
                                        <li class="item-cart">
                                            <div class="product-img-wrap">
                                                <a href="#"><img src="{{ asset('img/cart1.jpg') }}" alt="" class="img-reponsive"></a>
                                            </div>
                                            <div class="product-details">
                                                <div class="inner-left">
                                                    <div class="product-name"><a href="#">Harman Kardon Onyx Studio </a></div>
                                                    <div class="product-price">
                                                        $ 60.00 <span>( x2)</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="#" class="e-del"><i class="ion-ios-close-empty"></i></a>
                                        </li>
                                        <li class="item-cart">
                                            <div class="product-img-wrap">
                                                <a href="#"><img src="{{ asset('img/cart1.jpg') }}" alt="" class="img-reponsive"></a>
                                            </div>
                                            <div class="product-details">
                                                <div class="inner-left">
                                                    <div class="product-name"><a href="#">Harman Kardon Onyx Studio </a></div>
                                                    <div class="product-price">
                                                        $ 60.00 <span>( x2)</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="#" class="e-del"><i class="ion-ios-close-empty"></i></a>
                                        </li>
                                    </ul>
                                    <div class="bottom-cart">
                                        <div class="cart-price">
                                            <span>Subtotal</span>
                                            <span class="price-total">$ 120.00</span>
                                        </div>
                                        <div class="button-cart">
                                            <a href="#" class="cart-btn btn-viewcart">View Cart</a>
                                            <a href="#" class="cart-btn e-checkout btn-gradient">Checkout</a>
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
                        <a href="{{ route('catalog.index') }}"><button class="navbar-toggles"><span>Каталог товаров</span></button></a>
                    </div>
                </div>    
                <div class="col-lg-9 widget-left">
                    <div class="flex lr">
                        <nav class="main-menu" style="margin-left: 20px;>
                            <div class="collapse navbar-collapse" id="myNavbar">
                                <ul class="nav navbar-nav">
                                    <li class="level1"><a href="{{ route('shops') }}"><img src="{{ asset('img/icon-ship.png') }}" alt="">Магазины</a></li>
                                    <li class="level1"><a href="#"><img src="{{ asset('img/icon-heart3.png') }}" alt="">Понравившиеся</a></li>
                                    <li class="level1"><a href="{{ route('catalog.sale') }}"><img src="{{ asset('img/skidka-logo.png') }}" alt="">Распродажи сейчас<span class="h-ribbon h-pos e-red">sale</span></a></li>
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
                            <a href="#" class="fa fa-twitter"></a>
                            <a href="#" class="fa fa-dribbble"></a>
                            <a href="#" class="fa fa-behance"></a>
                            <a href="#" class="fa fa-instagram"></a>
                        </div>
                    </div>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-3 col-lg-3">
                    <div class="footer-block">
                        <h3 class="footer-block-title">Категории</h3>
                        <ul class="footer-block-content">
                            @foreach($categories->take(7) as $category)
                            <li><a href="{{ route('catalog.show', $category->alias ?? $category->id) }}">{{ $category->name }}</a></li>
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
        <a href="#" class="mobile-nav-item js-push-menu icon-pushmenu" title="Меню">
            <i class="fa fa-bars f-18"></i>
            <span class="mobile-nav-label">Каталог</span>
        </a>
        <a href="#" class="mobile-nav-item" title="Избранное">
            <i class="fa fa-heart f-18"></i>
            <span class="mobile-nav-label">Понравившиеся</span>
        </a>
        <a href="{{ route('catalog.sale') }}" class="mobile-nav-item" title="Распродажи">
            <img src="{{ asset('img/skidka-logo.png') }}" alt="Распродажи" style="width:20px; height:20px;">
            <span class="mobile-nav-label">Распродажи</span>
        </a>
        <a href="{{ route('shops') }}" class="mobile-nav-item" title="Магазины">
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
</body>

</html>
