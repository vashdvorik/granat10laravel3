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
<nav class="bimm-breadcrumbs"><a href="{{ $regionalUrl->route('home') }}">Главная</a><a href="{{ $regionalUrl->route('catalog.index') }}">Каталог</a>@foreach($breadcrumbs as $crumb)<a href="{{ $regionalUrl->route('catalog.show', ['alias' => $crumb->alias]) }}">{{ $crumb->name }}</a>@endforeach<span>{{ $product->name }}</span></nav>
<section class="bimm-detail">
    <div>
        <div class="bimm-gallery__main" data-bimm-gallery-main>
            @if($mainImage)<img src="{{ asset('storage/'.ltrim($mainImage->path, '/')) }}" alt="{{ $product->name }}" onerror="this.onerror=null;this.src='{{ asset('img/no-image.jpg') }}';">@else<img src="{{ asset('img/no-image.jpg') }}" alt="{{ $product->name }}">@endif
        </div>
        @if($images->count() > 1)
            <div class="bimm-gallery__thumbs">@foreach($images as $index => $image)<button class="bimm-gallery__thumb {{ $index === 0 ? 'is-active' : '' }}" type="button" data-bimm-gallery-thumb data-src="{{ asset('storage/'.ltrim($image->path, '/')) }}"><img src="{{ asset('storage/'.ltrim($image->path, '/')) }}" alt=""></button>@endforeach</div>
        @endif
    </div>
    <div class="bimm-detail__summary">
        @if($product->category)<a class="bimm-detail__category" href="{{ $regionalUrl->route('catalog.show', ['alias' => $product->category->alias]) }}">{{ $product->category->name }}</a>@endif
        <h1>{{ $product->name }}</h1>
        <div class="bimm-detail__price"><span>{{ number_format((float) $currentPrice, 0, '.', ' ') }} ₽</span>@if($hasDiscount)<del>{{ number_format((float) $product->price, 0, '.', ' ') }} ₽</del>@endif</div>
        <dl class="bimm-detail__meta">
            <div><dt>Продавец</dt><dd><a href="{{ $regionalUrl->route('seller', ['alias' => $product->seller->alias]) }}">{{ trim($product->seller->name, '" ') }}</a></dd></div>
            @if($product->seller->town)<div><dt>Город</dt><dd>{{ $product->seller->town }}</dd></div>@endif
            @if($product->seller->address)<div><dt>Адрес</dt><dd>{{ $product->seller->address }}</dd></div>@endif
        </dl>
        <div class="bimm-detail__actions">
            {{-- Заказ оформляется на отдельной странице --}}
            @if($orderAvailability->allowed)<a class="bimm-button bimm-button--gold" href="{{ route('orders.create', ['alias' => $product->alias]) }}">Оформить заказ <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>@endif
            @if($product->seller->phone)<a class="bimm-button {{ $orderAvailability->allowed ? 'bimm-button--light' : 'bimm-button--dark' }}" href="tel:{{ $phoneHref }}"><i class="fa-solid fa-phone"></i> {{ $orderAvailability->allowed ? 'Задать вопрос продавцу' : 'Позвонить продавцу' }}</a>@endif
        </div>
        @if(!$orderAvailability->allowed && ($product->seller->can_order || $product->can_order))<div class="bimm-flash" style="margin-top:16px">Заказ на сайте сейчас недоступен. Позвоните продавцу — он подскажет наличие и условия получения.</div>@endif
        <div class="bimm-trust"><div><strong><i class="fa-solid fa-comments"></i> Связь напрямую</strong>Задайте вопрос продавцу до покупки.</div><div><strong><i class="fa-solid fa-location-dot"></i> Удобное получение</strong>Способ и время согласуете с магазином.</div></div>
    </div>
</section>

<section class="bimm-detail__below">
    <div><h2>О товаре</h2><div class="bimm-description">{{ $product->desc ?: 'Продавец пока не добавил подробное описание.' }}</div></div>
    @if($features->isNotEmpty())<div><h2>Характеристики</h2><ul class="bimm-features">@foreach($features as $feature)<li>{{ $feature }}</li>@endforeach</ul></div>@endif
</section>

@if($relatedProducts->isNotEmpty())
    <section class="bimm-section"><div class="bimm-section__head"><div><span class="bimm-kicker">Рекомендуем</span><h2>{{ $relatedTitle }}</h2></div></div><div class="bimm-product-grid">@foreach($relatedProducts as $relatedProduct) @include('components.home-product-card', ['product' => $relatedProduct]) @endforeach</div></section>
@endif
@endsection
