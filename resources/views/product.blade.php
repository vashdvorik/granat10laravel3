@extends('main_layots')

@section('title', $product->name . ' | ГРАНАТ')
@section('og_type', 'product')

@php
    $images = $product->images
        ->sortBy(fn ($image) => sprintf('%d-%08d', $image->is_main ? 0 : 1, $image->order ?? 0))
        ->values();
    $mainImage = $images->first();
    $mainImageUrl = $mainImage ? url(Storage::url($mainImage->path)) : asset('img/no-image.jpg');
    $currentPrice = !is_null($product->lowprice) && $product->lowprice < $product->price
        ? $product->lowprice
        : $product->price;
    $hasDiscount = $currentPrice < $product->price;
    $discount = $hasDiscount && $product->price > 0
        ? round(100 - ($currentPrice / $product->price * 100))
        : null;
    $formatPrice = static fn ($price) => rtrim(rtrim(number_format((float) $price, 2, '.', ' '), '0'), '.');
    $phoneHref = preg_replace('/[^\d+]/', '', (string) $product->seller->phone);
    $features = collect([
        $product->features1,
        $product->features2,
        $product->features3,
        $product->features4,
    ])->filter()->values();

    $breadcrumbItems = [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Главная',
            'item' => route('home'),
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => 'Каталог',
            'item' => $regionalUrl->route('catalog.index'),
        ],
    ];

    foreach ($breadcrumbs as $crumb) {
        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => count($breadcrumbItems) + 1,
            'name' => $crumb->name,
            'item' => $regionalUrl->route('catalog.show', ['alias' => $crumb->alias]),
        ];
    }

    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => count($breadcrumbItems) + 1,
        'name' => $product->name,
        'item' => $regionalUrl->route('product.show', ['alias' => $product->alias]),
    ];

    $productSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'url' => $regionalUrl->route('product.show', ['alias' => $product->alias]),
        'description' => $product->desc ?: $product->name,
        'sku' => (string) $product->id,
        'offers' => [
            '@type' => 'Offer',
            'url' => $regionalUrl->route('product.show', ['alias' => $product->alias]),
            'priceCurrency' => 'RUB',
            'price' => number_format((float) $currentPrice, 2, '.', ''),
            'availability' => 'https://schema.org/InStock',
            'seller' => [
                '@type' => 'Organization',
                'name' => $product->seller->name,
            ],
        ],
    ];

    if ($images->isNotEmpty()) {
        $productSchema['image'] = $images
            ->map(fn ($image) => url(Storage::url($image->path)))
            ->values()
            ->all();
    }
@endphp

@section('og_image', $mainImageUrl)

@push('head')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $breadcrumbItems,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
<main class="product-page">
    <div class="container container-240">
        <nav aria-label="Хлебные крошки">
            <ol class="breadcrumb product-breadcrumbs">
                <li><a href="{{ route('home') }}">Главная</a></li>
                <li><a href="{{ $regionalUrl->route('catalog.index') }}">Каталог</a></li>
                @foreach($breadcrumbs as $crumb)
                    <li>
                        <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $crumb->alias]) }}">
                            {{ $crumb->name }}
                        </a>
                    </li>
                @endforeach
                <li class="active" aria-current="page">{{ $product->name }}</li>
            </ol>
        </nav>

        <section class="product-hero" aria-label="{{ $product->name }}">
            <div class="product-gallery">
                <div class="product-gallery__main" data-product-gallery>
                    @forelse($images as $index => $image)
                        <div class="product-gallery__slide{{ $index === 0 ? ' active' : '' }}" data-product-gallery-slide>
                            <img
                                src="{{ Storage::url($image->path) }}"
                                alt="{{ $image->alt ?? $product->name }}"
                                class="product-gallery__image"
                                width="800"
                                height="800"
                                @if($index > 0) loading="lazy" @else fetchpriority="high" @endif
                                onerror="this.onerror=null;this.src='{{ asset('img/no-image.jpg') }}';"
                            >
                        </div>
                    @empty
                        <div class="product-gallery__slide active" data-product-gallery-slide>
                            <img
                                src="{{ asset('img/no-image.jpg') }}"
                                alt="{{ $product->name }}"
                                class="product-gallery__image"
                                width="800"
                                height="800"
                                fetchpriority="high"
                            >
                        </div>
                    @endforelse
                </div>

                @if($discount)
                    <span class="product-discount">−{{ $discount }}%</span>
                @endif

                @if($images->count() > 1)
                    <div class="product-gallery__thumbs" aria-label="Фотографии товара">
                        @foreach($images as $index => $image)
                            <button type="button" class="product-gallery__thumb{{ $index === 0 ? ' active' : '' }}"
                                data-product-gallery-thumb
                                data-gallery-index="{{ $index }}"
                                aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                                aria-label="Показать фотографию {{ $index + 1 }}">
                                <img
                                    src="{{ Storage::url($image->path) }}"
                                    alt=""
                                    width="88"
                                    height="88"
                                    loading="lazy"
                                    onerror="this.onerror=null;this.src='{{ asset('img/no-image.jpg') }}';"
                                >
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="product-summary">
                @if($product->category)
                    <a class="product-summary__category"
                        href="{{ $regionalUrl->route('catalog.show', ['alias' => $product->category->alias]) }}">
                        {{ $product->category->name }}
                    </a>
                @endif

                <div class="product-summary__heading">
                    <h1 class="product-title">{{ $product->name }}@if($product->isVipActive()) <span class="vip-inline-tag">★ VIP</span>@endif</h1>
                    <button type="button" class="product-favorite wishlist-btn"
                        data-product-id="{{ $product->id }}" aria-label="Добавить товар в избранное">
                        <span class="icon-bg icon-wishlist" aria-hidden="true"></span>
                    </button>
                </div>

                <div class="product-price" aria-label="Цена товара">
                    <span class="{{ $hasDiscount ? 'red' : '' }}">{{ $formatPrice($currentPrice) }} руб.</span>
                    @if($hasDiscount)
                        <del>{{ $formatPrice($product->price) }} руб.</del>
                    @endif
                </div>

                <dl class="product-meta">
                    @if(!empty($product->seller->town))
                        <div><dt>Город</dt><dd>{{ $product->seller->town }}</dd></div>
                    @endif
                    <div>
                        <dt>Продавец</dt>
                        <dd>
                            <a href="{{ $regionalUrl->route('seller', ['alias' => $product->seller->alias]) }}">
                                {{ $product->seller->name }}
                            </a>
                        </dd>
                    </div>
                    @if(!empty($product->seller->address))
                        <div>
                            <dt>Адрес</dt>
                            <dd>
                                {{ $product->seller->address }}
                                @if(!empty($product->seller->link_map))
                                    <a class="product-map-link" href="{{ $product->seller->link_map }}"
                                        target="_blank" rel="noopener">На карте</a>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>

                <div class="product-actions">
                    @if(!empty($product->seller->phone))
                        <a class="product-action product-action--primary" href="tel:{{ $phoneHref }}">
                            <i class="fa fa-phone" aria-hidden="true"></i>
                            Позвонить продавцу
                            <span>{{ $product->seller->phone }}</span>
                        </a>
                    @endif
                    @if($orderAvailability->allowed)
                        <a class="product-action product-action--secondary" href="#order-form">
                            Оформить заказ
                        </a>
                    @endif
                </div>

                @if(!$orderAvailability->allowed && ($product->seller->can_order || $product->can_order))
                    <p class="product-order-status" role="status">
                        Заказ через сайт для этого товара сейчас недоступен.
                        @if(!empty($product->seller->phone)) Свяжитесь с продавцом по телефону. @endif
                    </p>
                @endif

                <div class="product-trust">
                    <div>
                        <i class="fa fa-credit-card" aria-hidden="true"></i>
                        <span><strong>Оплата продавцу</strong>Маркетплейс не принимает оплату.</span>
                    </div>
                    <div>
                        <i class="fa fa-truck" aria-hidden="true"></i>
                        <span><strong>Получение товара</strong>Условия согласовываются с продавцом.</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="product-details" aria-label="Информация о товаре">
            <div class="product-details__content">
                <h2>Описание</h2>
                @if(!empty($product->desc))
                    <div class="product-description" style="white-space: pre-line;">{{ $product->desc }}</div>
                @else
                    <p class="product-empty">Продавец пока не добавил подробное описание.</p>
                @endif
            </div>

            @if($features->isNotEmpty())
                <div class="product-details__features">
                    <h2>Характеристики</h2>
                    <ul>
                        @foreach($features as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        @if($orderAvailability->allowed)
            <div class="product-order-section">
                @include('orders._form')
            </div>
        @endif

        @if($relatedProducts->isNotEmpty())
            <section class="product-related" aria-labelledby="related-title">
                <div class="product-section-heading">
                    <h2 id="related-title">{{ $relatedTitle }}</h2>
                    @if($relatedFromSeller)
                        <a href="{{ $regionalUrl->route('seller', ['alias' => $product->seller->alias]) }}">
                            Смотреть все
                        </a>
                    @elseif($product->category)
                        <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $product->category->alias]) }}">
                            Смотреть все
                        </a>
                    @endif
                </div>
                <div class="product-related__grid">
                    @foreach($relatedProducts as $relatedProduct)
                        @include('components.home-product-card', ['product' => $relatedProduct])
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    @if(!empty($product->seller->phone) || $orderAvailability->allowed)
        <div class="product-mobile-actions hidden-sm hidden-md hidden-lg" aria-label="Действия с товаром">
            @if(!empty($product->seller->phone))
                <a href="tel:{{ $phoneHref }}" class="product-mobile-actions__call"
                    aria-label="Позвонить продавцу {{ $product->seller->phone }}">
                    <i class="fa fa-phone" aria-hidden="true"></i> {{ $product->seller->phone }}
                </a>
            @endif
            @if($orderAvailability->allowed)
                <a href="#order-form" class="product-mobile-actions__order">Заказать</a>
            @endif
        </div>
    @endif
</main>
@endsection

@push('styles')
<style>
.product-page{padding-bottom:70px;color:#222}.product-breadcrumbs{margin-bottom:36px}.product-hero{display:grid;grid-template-columns:minmax(0,1.08fr) minmax(360px,.92fr);gap:54px;align-items:start}.product-gallery{position:relative;min-width:0}.product-gallery__main{overflow:hidden;border:1px solid #eee;border-radius:14px;background:#fafafa}.product-gallery__slide{display:none;aspect-ratio:1/1}.product-gallery__slide.active{display:block}.product-gallery__image{width:100%;height:100%;object-fit:contain;display:block}.product-discount{position:absolute;top:18px;left:18px;z-index:2;display:flex;align-items:center;justify-content:center;width:62px;height:62px;border-radius:50%;background:#ff5151;color:#fff;font-size:16px;font-weight:700}.product-gallery__thumbs{display:flex;gap:12px;margin-top:14px;overflow-x:auto;padding-bottom:4px}.product-gallery__thumb{flex:0 0 88px;width:88px;height:88px;padding:3px;border:2px solid transparent;border-radius:9px;background:#fff}.product-gallery__thumb.active,.product-gallery__thumb:hover,.product-gallery__thumb:focus{border-color:#b766ef;outline:0}.product-gallery__thumb img{width:100%;height:100%;object-fit:cover;border-radius:5px}.product-summary__category{display:inline-block;margin-bottom:10px;color:#888}.product-summary__heading{display:flex;align-items:center;gap:18px}.product-summary h1{flex:1;min-width:0;margin:0;font-size:34px;line-height:1.2;font-weight:600}.product-favorite{display:flex;align-items:center;justify-content:center;flex:0 0 48px;width:48px;height:48px;padding:0;border:1px solid #e5e5e5;border-radius:50%;background:#fff}.product-favorite:focus{outline:2px solid #9d64e8;outline-offset:2px}.product-summary .product-price{display:flex;align-items:center;flex-wrap:wrap;gap:10px 16px;margin:24px 0 22px;font-size:34px;line-height:1.1}.product-summary .product-price span{font-size:34px;line-height:1.1}.product-summary .product-price del{font-size:17px;line-height:1;color:#999;white-space:nowrap}.product-meta{margin:0 0 24px}.product-meta>div{display:grid;grid-template-columns:92px minmax(0,1fr);align-items:center;gap:12px;padding:7px 0}.product-meta dt{font-weight:400;color:#888}.product-meta dd{display:flex;align-items:center;gap:8px;min-width:0;margin:0}.product-meta dd>a:not(.product-map-link){font-weight:600;color:#222}.product-map-link{display:inline-flex;align-items:center;justify-content:center;margin-left:0;padding:4px 10px;border-radius:5px;background:#ac62dd;color:#fff!important;white-space:nowrap}.product-actions{display:grid;gap:12px}.product-action{min-height:54px;display:flex;align-items:center;justify-content:center;gap:10px;border-radius:8px;font-size:16px;font-weight:600;text-align:center}.product-action--primary{background:linear-gradient(90deg,#b965ed,#4ed9ee);color:#fff}.product-action--primary:hover,.product-action--primary:focus{color:#fff;opacity:.9}.product-action--primary span{font-weight:400}.product-action--secondary{border:2px solid #a865e8;color:#7c42ae;background:#fff}.product-order-status{margin-top:16px;padding:13px 15px;border-radius:7px;background:#fff8e5;color:#6d5617}.product-trust{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:24px}.product-trust>div{display:flex;gap:12px;padding:15px;border:1px solid #e9e9e9;border-radius:9px}.product-trust i{margin-top:3px;color:#a764e4;font-size:20px}.product-trust span{font-size:13px;color:#777}.product-trust strong{display:block;margin-bottom:3px;color:#333;font-size:14px}.product-details{display:grid;grid-template-columns:1.4fr 1fr;gap:50px;margin-top:58px;padding:34px;border:1px solid #e8e8e8;border-radius:12px}.product-details h2,.product-related h2{margin:0 0 20px;font-size:26px}.product-description{font-size:16px;line-height:1.75;color:#555}.product-empty{color:#888}.product-details__features ul{margin:0;padding:0;list-style:none}.product-details__features li{position:relative;padding:10px 0 10px 22px;border-bottom:1px solid #eee}.product-details__features li:before{content:"";position:absolute;left:2px;top:17px;width:7px;height:7px;border-radius:50%;background:#aa64e4}.product-order-section{width:100%;margin:50px 0 0}.product-related{margin-top:58px}.product-section-heading{display:flex;align-items:center;justify-content:space-between;gap:20px}.product-section-heading h2{flex:1;min-width:0}.product-section-heading>a{flex:0 0 auto;color:#8b4fc1}.product-related__grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}.product-mobile-actions{position:fixed;right:0;bottom:58px;left:0;z-index:9997;display:grid;grid-auto-flow:column;grid-auto-columns:1fr;gap:8px;padding:10px 15px;background:rgba(255,255,255,.96);box-shadow:0 -4px 18px rgba(0,0,0,.12);backdrop-filter:blur(8px)}.product-mobile-actions a{display:flex;align-items:center;justify-content:center;gap:6px;min-height:44px;border-radius:7px;font-weight:600}.product-mobile-actions__call{border:1px solid #a45edc;color:#7f42ae}.product-mobile-actions__order{background:linear-gradient(90deg,#b965ed,#4ed9ee);color:#fff!important}
@media(max-width:1199px){.product-hero{gap:30px}.product-summary h1{font-size:29px}.product-related__grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:991px){.product-hero{grid-template-columns:1fr 1fr}.product-trust{grid-template-columns:1fr}.product-details{gap:30px}.product-related__grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:767px){.product-page{padding:0 0 132px;overflow:hidden}.product-hero{display:block}.product-gallery{margin:0 -15px}.product-gallery__main{border-radius:0}.product-gallery__thumbs{padding:0 15px 4px}.product-discount{top:16px;left:16px;width:52px;height:52px;font-size:14px}.product-summary{padding-top:28px}.product-summary__heading{align-items:flex-start;gap:10px}.product-summary h1{font-size:25px}.product-favorite{flex-basis:44px;width:44px;height:44px}.product-summary .product-price,.product-summary .product-price span{font-size:29px}.product-meta>div{grid-template-columns:75px minmax(0,1fr)}.product-meta dd{flex-wrap:wrap}.product-action--primary span{display:none}.product-trust{grid-template-columns:1fr}.product-trust>div{padding:12px}.product-trust span{font-size:12px}.product-details{display:block;margin-top:35px;padding:24px 20px}.product-details__features{margin-top:32px}.product-details h2,.product-related h2{font-size:21px}.product-order-section{margin-top:35px}.product-related{margin-top:40px}.product-related__grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.product-related__grid .home-product__body{padding:10px}.product-related__grid .home-product__seller{font-size:11px}.product-related__grid .home-product h3{min-height:40px;font-size:13px;line-height:1.35}.product-related__grid .home-product__price strong{font-size:16px}.product-section-heading{align-items:flex-start;flex-wrap:wrap;gap:4px}.product-section-heading h2{flex-basis:100%;margin-bottom:0}.product-section-heading>a{font-size:13px;white-space:nowrap}footer .col-xs-6{width:100%}footer .phone-desc{max-width:100%;overflow-wrap:anywhere}}
@media(max-width:480px){.product-actions{display:none}.product-related__grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var gallery = document.querySelector('[data-product-gallery]');
    if (!gallery) return;

    var slides = Array.prototype.slice.call(gallery.querySelectorAll('[data-product-gallery-slide]'));
    var thumbs = Array.prototype.slice.call(document.querySelectorAll('[data-product-gallery-thumb]'));
    if (!slides.length || !thumbs.length) return;

    function showSlide(index) {
        slides.forEach(function (slide, slideIndex) {
            slide.classList.toggle('active', slideIndex === index);
        });

        thumbs.forEach(function (thumb, thumbIndex) {
            var isActive = thumbIndex === index;
            thumb.classList.toggle('active', isActive);
            thumb.setAttribute('aria-current', isActive ? 'true' : 'false');
        });
    }

    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            var index = parseInt(thumb.getAttribute('data-gallery-index'), 10);
            if (Number.isInteger(index) && slides[index]) {
                showSlide(index);
            }
        });
    });
});
</script>
@endpush
