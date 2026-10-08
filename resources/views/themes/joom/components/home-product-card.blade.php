@php
    $image = $product->images->where('is_main', true)->first() ?? $product->images->first();
    $currentPrice = $product->lowprice && $product->lowprice < $product->price ? $product->lowprice : $product->price;
    $discount = $currentPrice < $product->price && $product->price > 0 ? round(100 - ($currentPrice / $product->price * 100)) : null;
@endphp
<article class="joom-product">
    <div class="joom-product__media">
        <a href="{{ $regionalUrl->route('product.show', ['alias' => $product->alias]) }}" aria-label="Открыть товар: {{ $product->name }}">
            @if($image)
                <img src="{{ asset('storage/'.ltrim($image->path, '/')) }}" alt="{{ $product->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('img/no-image.jpg') }}';">
            @else
                <span class="joom-product__placeholder"><i class="fa-regular fa-image" aria-hidden="true"></i><small>Нет фото</small></span>
            @endif
        </a>
        @if($discount)<span class="joom-product__badge">−{{ $discount }}%</span>@endif
        <button class="joom-product__favorite" type="button" data-joom-favorite="{{ $product->id }}" aria-pressed="false" aria-label="Добавить в избранное"><i class="fa-regular fa-heart"></i></button>
        @if($product->delivery_enabled)<span class="joom-product__delivery"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i> Доставка</span>@endif
    </div>
    <div class="joom-product__body">
        <a class="joom-product__seller" href="{{ $regionalUrl->route('seller', ['alias' => $product->seller->alias]) }}">{{ trim($product->seller->name, '" ') }}</a>
        <h3><a href="{{ $regionalUrl->route('product.show', ['alias' => $product->alias]) }}">{{ $product->name }}</a></h3>
        <div class="joom-product__meta"><span class="joom-product__rating"><i class="fa-solid fa-location-dot"></i> {{ $product->seller->town ?? 'ПМР' }}</span><span>местный продавец</span></div>
        <div class="joom-product__price-line">
            <strong>{{ number_format((float) $currentPrice, 0, '.', ' ') }} ₽</strong>
            @if($discount)<del>{{ number_format((float) $product->price, 0, '.', ' ') }} ₽</del>@endif
        </div>
    </div>
</article>
