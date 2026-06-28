@php
    $mainImage = $product->images->where('is_main', true)->first() ?? $product->images->first();
    $currentPrice = !is_null($product->lowprice) && $product->lowprice < $product->price
        ? $product->lowprice
        : $product->price;
    $hasDiscount = $currentPrice < $product->price;
    $discount = $hasDiscount && $product->price > 0
        ? round(100 - ($currentPrice / $product->price * 100))
        : null;
    $formatPrice = static fn ($price) => rtrim(rtrim(number_format((float) $price, 2, '.', ' '), '0'), '.');
@endphp

<article class="home-product">
    <a class="home-product__image" href="{{ $regionalUrl->route('product.show', ['alias' => $product->alias]) }}">
        <img
            src="{{ $mainImage ? Storage::url($mainImage->path) : asset('img/no-image.jpg') }}"
            alt="{{ $mainImage->alt ?? $product->name }}"
            width="420"
            height="420"
            loading="lazy"
            onerror="this.onerror=null;this.src='{{ asset('img/no-image.jpg') }}';"
        >
    </a>

    @if($discount)
        <span class="home-product__discount">−{{ $discount }}%</span>
    @endif

    <button type="button" class="home-product__favorite wishlist-btn"
        data-product-id="{{ $product->id }}" aria-label="Добавить товар в избранное">
        <span class="icon-bg icon-wishlist" aria-hidden="true"></span>
    </button>

    <div class="home-product__body">
        <span class="home-product__seller">
            {{ trim($product->seller->name, '" ') }}
        </span>
        <h3>
            <a href="{{ $regionalUrl->route('product.show', ['alias' => $product->alias]) }}">
                {{ $product->name }}
            </a>
        </h3>
        <p class="home-product__price">
            <strong>{{ $formatPrice($currentPrice) }} руб.</strong>
            @if($hasDiscount)
                <del>{{ $formatPrice($product->price) }} руб.</del>
            @endif
        </p>
    </div>
</article>
