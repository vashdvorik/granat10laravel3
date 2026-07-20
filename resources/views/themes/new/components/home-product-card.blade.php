@php
    $image = $product->images->where('is_main', true)->first() ?? $product->images->first();
    $currentPrice = $product->lowprice && $product->lowprice < $product->price ? $product->lowprice : $product->price;
    $discount = $currentPrice < $product->price && $product->price > 0 ? round(100 - ($currentPrice / $product->price * 100)) : null;
@endphp
<article class="bimm-product">
    <a class="bimm-product__image" href="{{ $regionalUrl->route('product.show', ['alias' => $product->alias]) }}">
        @if($image)
            <img src="{{ asset('storage/'.ltrim($image->path, '/')) }}" alt="{{ $product->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('img/no-image.jpg') }}';">
        @else
            <span class="bimm-product__placeholder">{{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</span>
        @endif
        @if($discount)<span class="bimm-product__badge">−{{ $discount }}%</span>@elseif($product->is_vip)<span class="bimm-product__badge">Выбор BIMM</span>@endif
        <button class="bimm-product__heart" type="button" data-bimm-favorite="{{ $product->id }}" aria-label="Добавить в избранное"><i class="fa-regular fa-heart"></i></button>
    </a>
    <div class="bimm-product__body">
        <a class="bimm-product__seller" href="{{ $regionalUrl->route('seller', ['alias' => $product->seller->alias]) }}">{{ trim($product->seller->name, '" ') }}</a>
        <h3><a href="{{ $regionalUrl->route('product.show', ['alias' => $product->alias]) }}">{{ $product->name }}</a></h3>
        <div class="bimm-product__bottom">
            <div>
                <span class="bimm-product__price">{{ number_format((float) $currentPrice, 0, '.', ' ') }} ₽</span>
                @if($discount)<del class="bimm-product__old">{{ number_format((float) $product->price, 0, '.', ' ') }} ₽</del>@endif
            </div>
            <a class="bimm-product__open" href="{{ $regionalUrl->route('product.show', ['alias' => $product->alias]) }}" aria-label="Открыть товар"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
        </div>
    </div>
</article>
