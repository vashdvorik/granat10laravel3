@extends('main_layots')

@php
    $image = $product->images->sortBy(fn ($img) => sprintf('%d-%08d', $img->is_main ? 0 : 1, $img->order ?? 0))->first();
    $imageUrl = $image ? asset('storage/'.ltrim($image->path, '/')) : asset('img/no-image.jpg');
    $currentPrice = ! is_null($product->lowprice) && $product->lowprice < $product->price ? $product->lowprice : $product->price;
    $hasDiscount = $currentPrice < $product->price;
    $sellerName = trim((string) $product->seller->name, '" ');
    $money = static fn ($value): string => number_format((float) $value, 0, '.', ' ').' ₽';
    $productUrl = $regionalUrl->route('product.show', ['alias' => $product->alias]);
    $orderBoxTitle = 'Ваши данные';
    $orderBoxLead = 'Продавец позвонит, подтвердит наличие и договорится с вами о получении.';
@endphp

@section('title', 'Оформление заказа: '.$product->name.' — BIMM')

{{-- noindex ставит общий SeoService для всех маршрутов orders.* --}}

@section('content')
<main class="container container-240 checkout-page">
    <nav class="checkout-crumbs" aria-label="Хлебные крошки">
        <a href="{{ $regionalUrl->route('home') }}">Главная</a>
        <span aria-hidden="true">›</span>
        <a href="{{ $productUrl }}">{{ $product->name }}</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Оформление заказа</span>
    </nav>

    <h1 class="checkout-title">Оформление заказа</h1>

    <div class="checkout-layout">
        <aside class="checkout-product" aria-label="Ваш товар">
            <a class="checkout-product__image" href="{{ $productUrl }}">
                <img src="{{ $imageUrl }}" alt="{{ $product->name }}"
                     onerror="this.onerror=null;this.src='{{ asset('img/no-image.jpg') }}';">
            </a>
            <div class="checkout-product__info">
                <a class="checkout-product__name" href="{{ $productUrl }}">{{ $product->name }}</a>
                <div class="checkout-product__price">
                    <strong>{{ $money($currentPrice) }}</strong>
                    @if($hasDiscount)<del>{{ $money($product->price) }}</del>@endif
                </div>
                <div class="checkout-product__seller">Продавец: <a href="{{ $regionalUrl->route('seller', ['alias' => $product->seller->alias]) }}">{{ $sellerName }}</a></div>
                @if(filled($product->seller->town))
                    <div class="checkout-product__seller">{{ $product->seller->town }}</div>
                @endif
                <a class="checkout-product__back" href="{{ $productUrl }}">← Вернуться к товару</a>
            </div>
        </aside>

        <div class="checkout-form">
            @include('orders._form')
        </div>
    </div>
</main>

<style>
/* Страница стоит в сетке сайта (container-240: те же боковые отступы, что у шапки и других страниц), а не узкой колонкой по центру */
.checkout-page{padding-top:22px;padding-bottom:70px;color:#222}
.checkout-crumbs{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 18px;font-size:13px;color:#888}
.checkout-crumbs a{color:#666}.checkout-crumbs a:hover{color:#222}
.checkout-title{margin:0 0 22px;font-size:32px;line-height:1.15;font-weight:700}
.checkout-layout{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:26px;align-items:start;max-width:1140px}
.checkout-form{order:1;min-width:0}
.checkout-product{order:2;position:sticky;top:20px;display:flex;gap:14px;padding:16px;border:1px solid #e4e4ea;border-radius:14px;background:#fff}
.checkout-product__image{flex:0 0 96px;width:96px;height:96px;overflow:hidden;border-radius:10px;background:#f4f4f6}
.checkout-product__image img{display:block;width:100%;height:100%;object-fit:cover}
.checkout-product__info{display:flex;flex-direction:column;gap:5px;min-width:0}
.checkout-product__name{font-size:16px;font-weight:700;line-height:1.3;color:#222;overflow-wrap:anywhere}
.checkout-product__price strong{font-size:20px;font-weight:700}.checkout-product__price del{margin-left:8px;font-size:14px;color:#999}
.checkout-product__seller{font-size:13px;color:#777}.checkout-product__seller a{color:#444;font-weight:600}
.checkout-product__back{margin-top:4px;font-size:13px;font-weight:600;color:#7c42ae}
.theme-new .checkout-product__back{color:var(--bimm-gold,#555dff)}.theme-joom .checkout-product__back{color:var(--joom-red,#555dff)}
.theme-new .checkout-product,.theme-joom .checkout-product{border-color:var(--bimm-line,var(--joom-line,#e6e6e6))}
@media(max-width:767px){.checkout-page{padding-bottom:110px}}
@media(max-width:991px){
    .checkout-layout{grid-template-columns:1fr;gap:16px}
    .checkout-product{order:1;position:static;padding:12px}
    .checkout-form{order:2}
    .checkout-product__image{flex-basis:72px;width:72px;height:72px}
    .checkout-title{font-size:25px;margin-bottom:16px}
}
</style>
@endsection
