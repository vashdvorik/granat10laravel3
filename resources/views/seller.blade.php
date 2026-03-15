@extends('main_layots')
@section('title', 'Продавцы Тирасполя | Маркетплейс ПМР')

@section('content')
{{--
<div class="container container-240">

    <ul class="breadcrumb v4">
        <li><a href="{{ route('home') }}">Главная</a></li>
        <li class="active">Магазины</li>
    </ul>
  
    <div class="blog spc1">
        <div class="blog-grid">
            <h1 class="blog-heading text-center">{{ $seller->name ?? 'Название продавца' }}</h1>
            <div class="row">
        
            </div>
        </div>
        <ul class="pagination">
            <li class="active"><a href="#">1</a></li>
            <li><a href="#">2</a></li>
            <li><a href="#"><i class="ion-ios-arrow-forward"></i></a></li>
        </ul>
    </div>
</div>
--}}

<div class="container container-240">
    <div class="blog-banner pd-banner v2">
        <a href="#" class="effect_img2"><img src="img/blog/blog-banner.png" alt="" class="img-reponsive"></a> 
    </div>
    <div class="blog spc1">
        <ul class="breadcrumb">
        <li><a href="{{ route('home') }}">Главная</a></li>
        <li><a href="{{ route('shops') }}">Магазины</a></li>
        <li class="active">{{ $seller->name ?? 'Название продавца' }}</li>
        </ul>
        <div class="blog-single-post">
            <div class="row">
                <div class="blog-content col-md-12 col-xs-12">
                    <div class="blog-post-item v2">
                        <div class="blog-img">
                            <a href="#" class="hover-images"><img src="{{ asset('img/blog/single-post.jpg') }}" alt="" class="img-reponsive"></a>
                            <div class="blog-post-date e-gradient abs v2">
                                <span class="b-date">{{ $seller->products_count ?? 0 }}</span>
                                <span class="b-month">ТОВ</span>
                            </div>
                        </div>
                        <div class="blog-info-bd">
                            <div class="blog-info">
                                <h3 class="blog-post-title v2">{{ $seller->name ?? 'Название продавца' }}</h3>
                                <div class="blog-post-desc">
                                    <p>{{ $seller->description ?? '' }}</p>
                                   
                                </div>
                            </div>
                            <div class="blog-post-author v2">
                                <div class="seller-contacts-info">
                                    <div class="contacts-wrapper">
                                        <!-- Контактная информация слева -->
                                        <div class="contacts-text">
                                            @if($seller->phone)
                                                <div class="contact-detail">
                                                    <i class="ion-ios-telephone"></i>
                                                    <span><strong>Телефон:</strong> <a href="tel:{{ $seller->phone }}">{{ $seller->phone }}</a></span>
                                                </div>
                                            @endif

                                            @if($seller->town || $seller->sellerplace)
                                                <div class="contact-detail">
                                                    <i class="ion-ios-navigate"></i>
                                                    <span><strong>Город:</strong> {{ $seller->town }}{{ $seller->town && $seller->sellerplace ? ', ' : '' }}{{ $seller->sellerplace }}</span>
                                                </div>
                                            @endif

                                            @if($seller->address)
                                                <div class="contact-detail">
                                                    <i class="ion-ios-location"></i>
                                                    <span><strong>Адрес:</strong> {{ $seller->address }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Кнопки справа -->
                                        <div class="buttons-group">
                                            @if($seller->link_map)
                                                <a href="{{ $seller->link_map }}" target="_blank" rel="noopener" class="btn-seller-action btn-map">
                                                    <i class="ion-ios-location"></i> На карте
                                                </a>
                                            @endif
                                        
                                            @if($seller->link)
                                                <a href="{{ $seller->link }}" target="_blank" rel="noopener" class="btn-seller-action btn-website">
                                                    <i class="ion-ios-world"></i> Сайт
                                                </a>
                                            @endif



                                            @if($seller->telegram || $seller->instagram || $seller->viber)
                                                <div class="social-buttons">
                                                    @if($seller->telegram)
                                                        @php
                                                            // Формируем правильную ссылку для Telegram
                                                            $telegramLink = $seller->telegram;
                                                            if (!str_starts_with($telegramLink, 'http://') && !str_starts_with($telegramLink, 'https://')) {
                                                                // Убираем @ если есть и формируем ссылку
                                                                $username = ltrim($telegramLink, '@');
                                                                $telegramLink = 'https://t.me/' . $username;
                                                            }
                                                        @endphp
                                                        <a href="{{ $telegramLink }}" target="_blank" rel="noopener" class="social-btn telegram" title="Telegram">
                                                            <i class="fab fa-telegram"></i>
                                                        </a>
                                                    @endif
                                                    @if($seller->instagram)
                                                        @php
                                                            // Формируем правильную ссылку для Instagram
                                                            $instagramLink = $seller->instagram;
                                                            if (!str_starts_with($instagramLink, 'http://') && !str_starts_with($instagramLink, 'https://')) {
                                                                // Убираем @ если есть и формируем ссылку
                                                                $username = ltrim($instagramLink, '@');
                                                                $instagramLink = 'https://instagram.com/' . $username;
                                                            }
                                                        @endphp
                                                        <a href="{{ $instagramLink }}" target="_blank" rel="noopener" class="social-btn instagram" title="Instagram">
                                                            <i class="fab fa-instagram"></i>
                                                        </a>
                                                    @endif
                                                    @if($seller->viber)
                                                        @php
                                                            // Формируем правильную ссылку для Viber
                                                            $viberLink = $seller->viber;
                                                            if (!str_starts_with($viberLink, 'http://') && !str_starts_with($viberLink, 'https://') && !str_starts_with($viberLink, 'viber://')) {
                                                                // Если это номер телефона - формируем viber ссылку
                                                                $phone = preg_replace('/[^0-9+]/', '', $viberLink);
                                                                $viberLink = 'viber://chat?number=' . $phone;
                                                            }
                                                        @endphp
                                                        <a href="{{ $viberLink }}" target="_blank" rel="noopener" class="social-btn viber" title="Viber">
                                                            <i class="fab fa-viber"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ТОВАРЫ ПРОДАВЦА --}}
        @if($products->count() > 0)
            <div class="seller-products-section">
                <h2 class="seller-products-title">Товары магазина</h2>
                <div class="product-collection-grid product-grid spc1">
                    <div class="row" id="seller-products-grid">
                        @foreach($products as $product)
                            @include('components.productcategories', ['product' => $product])
                        @endforeach
                    </div>
                </div>

                {{-- ПАГИНАЦИЯ --}}
                @if($products->hasPages())
                    {{-- Desktop --}}
                    <div class="pagination-desktop hidden-xs hidden-sm pd-middle space-v1">
                        {{ $products->links('vendor.pagination.custom') }}
                    </div>

                    {{-- Mobile: кнопка "Загрузить еще" --}}
                    @if($products->currentPage() < $products->lastPage())
                        <div class="load-more-wrapper hidden-md hidden-lg text-center">
                            <button class="btn btn-load-more"
                                    data-url="{{ route('seller.loadMore', $seller->alias) }}"
                                    data-current-page="{{ $products->currentPage() }}"
                                    data-last-page="{{ $products->lastPage() }}"
                                    data-container="#seller-products-grid">
                                <i class="ion-load-c"></i> Загрузить еще
                            </button>
                        </div>
                    @endif
                @endif
            </div>
        @endif

    </div>
</div>

<style>
    .seller-products-section {
        margin-top: 40px;
    }

    .seller-products-title {
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .blog-post-author.v2 {
        width: 100%;
    }

    .seller-contacts-info {
        padding: 20px 0;
        width: 100%;
    }

    /* Обёртка: два блока рядом */
    .contacts-wrapper {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    /* Текст — слева, горизонтально на десктопе */
    .contacts-text {
        display: flex;
        flex-direction: row;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .contact-detail {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        white-space: nowrap;
    }

    .contact-detail i {
        font-size: 18px;
        color: #e91e63;
        flex-shrink: 0;
    }

    .contact-detail strong {
        margin-right: 4px;
    }

    .contact-detail a {
        color: #e91e63;
        text-decoration: none;
    }

    .contact-detail a:hover {
        text-decoration: underline;
    }

    /* Кнопки — справа, горизонтально, прижаты вправо */
    .buttons-group {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn-seller-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        color: #fff !important;
        text-decoration: none !important;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
        white-space: nowrap;
    }

    .btn-seller-action i {
        font-size: 16px;
    }

    .btn-website {
        background: #667eea;
    }

    .btn-website:hover {
        background: #5568d3;
        transform: translateY(-2px);
    }

    .btn-map {
        background: #26a69a;
    }

    .btn-map:hover {
        background: #00897b;
        transform: translateY(-2px);
    }

    .social-buttons {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .social-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        color: #fff !important;
        text-decoration: none !important;
        transition: all 0.3s;
        font-size: 20px;
    }

    .social-btn.telegram { background: #0088cc; }
    .social-btn.telegram:hover { background: #006699; transform: scale(1.1); }

    .social-btn.instagram { background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }
    .social-btn.instagram:hover { transform: scale(1.1); }

    .social-btn.viber { background: #7360f2; }
    .social-btn.viber:hover { background: #5d4dd4; transform: scale(1.1); }

    /* Мобильные */
    @media (max-width: 768px) {
        .contacts-wrapper {
            flex-direction: column;
            align-items: flex-start;
        }

        .contacts-text {
            flex-direction: column;
            align-items: flex-start;
        }

        .buttons-group {
            justify-content: flex-start;
        }
    }
</style>

@endsection