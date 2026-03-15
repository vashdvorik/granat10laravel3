{{-- КАРТОЧКА ТОВАРА, КОТОРАЯ ОТОБРАЖАЕТСЯ В КАТЕГОРИЯХ --}}
<div class="col-xs-6 col-sm-6 col-md-4 col-lg-4 product-item">
    <div class="pd-bd product-inner">
        <div class="product-img">
            @php
                $mainImage = $product->images->where('is_main', true)->first() ?? $product->images->first();
                $discount = null;
                if (!is_null($product->lowprice) && $product->price > 0) {
                    $discount = round(100 - ($product->lowprice / $product->price * 100));
                }
            @endphp
            <a href="{{ route('product.show', $product->alias) }}">
                @if($mainImage && !empty($mainImage->path))
                    <img src="{{ Storage::url($mainImage->path) }}"
                        alt="{{ $mainImage->alt ?? $product->name }}"
                        class="img-reponsive"
                        onerror="this.onerror=null;this.src='{{ asset('img/no-image.jpg') }}';">
                @else
                    <img src="{{ asset('img/no-image.jpg') }}" alt="{{ $product->name }}" class="img-reponsive">
                @endif
            </a>
            @if($discount)
                <div class="ribbon-price red"><span>- {{ $discount }}% </span></div>
            @endif
        </div>
        <div class="product-info">
            <div class="color-group"></div>
            <div class="element-list element-list-left">
                <ul class="desc-list">
                    @foreach([$product->features1, $product->features2, $product->features3, $product->features4] as $feature)
                        @if(!empty($feature))
                            <li>{{ $feature }}</li>
                        @endif
                    @endforeach
                </ul>
            </div>
            <div class="element-list element-list-middle">
                <div class="product-rating bd-rating">
                    <span class="star star-5"></span>
                    <span class="star star-4"></span>
                    <span class="star star-3"></span>
                    <span class="star star-2"></span>
                    <span class="star star-1"></span>
                    <div class="number-rating">( 896 reviews )</div>
                </div>
                <p class="product-cate"><a href="{{ route('seller', $product->seller->alias) }}">{{ $product->seller->name }}</a></p>
                <h3 class="product-title"><a href="{{ route('product.show', $product->alias) }}">{{ $product->name }}</a></h3>
                <div class="product-bottom">
                    <div class="product-price">
                        @if(!is_null($product->lowprice))
                            <span class="red">{{ $product->lowprice }} руб.</span>
                            <span class="old">{{ $product->price }} руб.</span>
                        @else
                            <span>{{ $product->price }} руб.</span>
                        @endif
                    </div>
                    <a href="#" class="btn-icon btn-view">
                        <span class="icon-bg icon-view"></span>
                    </a>
                </div>
                <div class="product-bottom-group">
                    <a href="{{ route('product.show', $product->alias) }}" class="btn-icon" title="Позвонить">
                        <span class="icon-bg icon-call"></span>
                    </a>
                    <a href="#" class="btn-icon wishlist-btn" data-product-id="{{ $product->id }}">
                        <span class="icon-bg icon-wishlist"></span>
                    </a>
                    <a href="{{ route('seller', $product->seller->alias) }}" class="btn-icon" title="{{ $product->seller->name }}">
                        <span class="icon-bg icon-shop"></span>
                    </a>
                </div>
            </div>
            <div class="product-button-group">
                <a href="{{ route('product.show', $product->alias) }}" class="btn-icon" title="Позвонить">
                    <span class="icon-bg icon-call"></span>
                </a>
                <a href="#" class="btn-icon wishlist-btn" data-product-id="{{ $product->id }}">
                    <span class="icon-bg icon-wishlist"></span>
                </a>
                <a href="{{ route('seller', $product->seller->alias) }}" class="btn-icon" title="{{ $product->seller->name }}">
                    <span class="icon-bg icon-shop"></span>
                </a>
            </div>
        </div>
    </div>
</div>