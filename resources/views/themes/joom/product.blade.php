@extends('main_layots')

@php
    $images = $product->images->sortBy(fn ($image) => sprintf('%d-%08d', $image->is_main ? 0 : 1, $image->order ?? 0))->values();
    $mainImage = $images->first();
    $currentPrice = $product->lowprice && $product->lowprice < $product->price ? $product->lowprice : $product->price;
    $hasDiscount = $currentPrice < $product->price;
    $discount = $hasDiscount && $product->price > 0 ? round(100 - ($currentPrice / $product->price * 100)) : null;
    $features = collect([$product->features1, $product->features2, $product->features3, $product->features4])->filter()->values();
    $phoneHref = preg_replace('/[^\d+]/', '', (string) $product->seller->phone);
@endphp

@section('title', $product->name.' — BIMM')

@section('content')
<nav class="joom-breadcrumbs" aria-label="Хлебные крошки"><a href="{{ $regionalUrl->route('home') }}">Главная</a><a href="{{ $regionalUrl->route('catalog.index') }}">Каталог</a>@foreach($breadcrumbs as $crumb)<a href="{{ $regionalUrl->route('catalog.show', ['alias' => $crumb->alias]) }}">{{ $crumb->name }}</a>@endforeach<span>{{ $product->name }}</span></nav>

<section class="joom-product-detail">
    <div class="joom-gallery">
        <div class="joom-gallery__main" data-joom-gallery-main>
            @if($mainImage)<img src="{{ asset('storage/'.ltrim($mainImage->path, '/')) }}" alt="{{ $product->name }}" onerror="this.onerror=null;this.src='{{ asset('img/no-image.jpg') }}';">@else<img src="{{ asset('img/no-image.jpg') }}" alt="{{ $product->name }}">@endif
            @if($discount)<span class="joom-product__badge">−{{ $discount }}%</span>@endif
        </div>
        @if($images->count() > 1)
            <div class="joom-gallery__thumbs">@foreach($images as $index => $image)<button class="joom-gallery__thumb {{ $index === 0 ? 'is-active' : '' }}" type="button" data-joom-gallery-thumb data-src="{{ asset('storage/'.ltrim($image->path, '/')) }}"><img src="{{ asset('storage/'.ltrim($image->path, '/')) }}" alt=""></button>@endforeach</div>
        @endif
    </div>
    <div class="joom-product-detail__summary">
        @if($product->category)<a class="joom-detail__category" href="{{ $regionalUrl->route('catalog.show', ['alias' => $product->category->alias]) }}">{{ $product->category->name }}</a>@endif
        <h1>{{ $product->name }}@if($product->isVipActive()) <span class="vip-inline-tag"><i class="fa-solid fa-star"></i> VIP</span>@endif</h1>
        <div class="joom-detail__meta"><span><i class="fa-solid fa-location-dot"></i> {{ $product->seller->town ?? 'ПМР' }}</span><span>Местный продавец</span></div>
        <div class="joom-detail__price"><strong>{{ number_format((float) $currentPrice, 0, '.', ' ') }} ₽</strong>@if($hasDiscount)<del>{{ number_format((float) $product->price, 0, '.', ' ') }} ₽</del><span>−{{ $discount }}%</span>@endif</div>
        <div class="joom-detail__seller"><span class="joom-detail__seller-logo">{{ mb_strtoupper(mb_substr(trim($product->seller->name, '" '), 0, 1)) }}</span><span><small>Продавец</small><a href="{{ $regionalUrl->route('seller', ['alias' => $product->seller->alias]) }}">{{ trim($product->seller->name, '" ') }}</a></span></div>
        <div class="joom-detail__actions">
            @if($orderAvailability->allowed)<a class="joom-button joom-button--primary" href="#order-form">Оформить заказ <i class="fa-solid fa-arrow-right"></i></a>@endif
            @if($product->seller->phone)<a class="joom-button joom-button--quiet" href="tel:{{ $phoneHref }}"><i class="fa-solid fa-phone"></i> Связаться</a>@endif
        </div>
        <div class="joom-detail__benefits"><div><i class="fa-solid fa-truck-fast"></i><span><strong>{{ $product->delivery_enabled ? 'Доставка доступна' : 'Получение у продавца' }}</strong>Уточните способ и стоимость напрямую.</span></div><div><i class="fa-solid fa-comments"></i><span><strong>Связь напрямую</strong>Задайте вопрос до покупки.</span></div></div>
    </div>
</section>

<section class="joom-detail-tabs">
    <div><h2>О товаре</h2><p>{{ $product->desc ?: 'Продавец пока не добавил подробное описание.' }}</p></div>
    @if($features->isNotEmpty())<div><h2>Характеристики</h2><ul>@foreach($features as $feature)<li>{{ $feature }}</li>@endforeach</ul></div>@endif
</section>

@if($orderAvailability->allowed)<div class="joom-order-wrap">@include('orders._form')</div>@endif

@if($relatedProducts->isNotEmpty())
    <section class="joom-section"><div class="joom-section__head"><div><span class="joom-eyebrow">ЕЩЁ ПОСМОТРИ</span><h2>{{ $relatedTitle }}</h2></div></div><div class="joom-product-row">@foreach($relatedProducts as $relatedProduct) @include('components.home-product-card', ['product' => $relatedProduct]) @endforeach</div></section>
@endif
@endsection
