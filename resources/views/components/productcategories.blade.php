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
                </div>
                <div class="product-bottom-group">
                    <a href="javascript:void(0)" class="btn-icon btn-phone-popover"
                       data-phone="{{ $product->seller->phone ?? 'Не указан' }}"
                       data-seller="{{ $product->seller->name }}">
                        <span class="icon-bg icon-call"></span>
                    </a>
                    <a href="#" class="btn-icon wishlist-btn" data-product-id="{{ $product->id }}">
                        <span class="icon-bg icon-wishlist"></span>
                    </a>
                    <a href="{{ route('seller', $product->seller->alias) }}" class="btn-icon" title="Перейти к продавцу">
                        <span class="icon-bg icon-shop"></span>
                    </a>
                </div>
            </div>
            <div class="product-button-group">
                <a href="javascript:void(0)" class="btn-icon btn-phone-popover"
                   data-phone="{{ $product->seller->phone ?? 'Не указан' }}"
                   data-seller="{{ $product->seller->name }}">
                    <span class="icon-bg icon-call"></span>
                </a>
                <a href="#" class="btn-icon wishlist-btn" data-product-id="{{ $product->id }}">
                    <span class="icon-bg icon-wishlist"></span>
                </a>
                <a href="{{ route('seller', $product->seller->alias) }}" class="btn-icon" title="Перейти  к продавцу">
                    <span class="icon-bg icon-shop"></span>
                </a>
            </div>
        </div>
    </div>
</div>

@pushOnce('styles')
<style>
.phone-popover .popover-title {
    font-size: 13px;
    color: #999;
    background: #fff;
    border-bottom: 1px solid #f0f0f0;
    padding: 8px 14px;
    border-radius: 6px 6px 0 0;
}
.phone-popover .popover-content {
    padding: 14px 18px;
    text-align: center;
}
.phone-popover .phone-number {
    font-size: 22px;
    font-weight: 700;
    color: #222;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 12px;
}
.phone-popover .btn-call {
    display: inline-block;
    padding: 8px 24px;
    border-radius: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff !important;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none !important;
    transition: opacity .2s;
}
.phone-popover .btn-call:hover { opacity: .85; }
.phone-popover.top > .arrow { border-top-color: #e0e0e0; }
.phone-popover.top > .arrow::after { border-top-color: #fff; }
.phone-popover {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,.15);
    min-width: 220px;
}
</style>
@endPushOnce

@pushOnce('scripts')
<script>
(function ($) {
    function initPhonePopovers() {
        $('.btn-phone-popover').not('[data-popover-init]').each(function () {
            var $btn = $(this);
            $btn.attr('data-popover-init', '1').popover({
                html      : true,
                trigger   : 'click',
                placement : 'top',
                container : 'body',
                template  : '<div class="popover phone-popover" role="tooltip"><div class="arrow"></div><div class="popover-content"></div></div>',
                title     : false,
                content   : function () {
                    var phone = String($btn.data('phone') || 'Не указан');
                    var tel   = 'tel:' + phone.replace(/[^+\d]/g, '');
                    return '<span class="phone-number">' + phone + '</span>'
                         + '<a href="' + tel + '" class="btn-call">Позвонить</a>';
                }
            });
        });
    }

    $(document).ready(function () {
        initPhonePopovers();

        // Один открытый поповер за раз
        $(document).on('show.bs.popover', '.btn-phone-popover', function () {
            $('.btn-phone-popover').not(this).popover('hide');
        });

        // Закрыть при клике вне поповера
        $(document).on('click touchstart', function (e) {
            if (!$(e.target).closest('.btn-phone-popover, .phone-popover').length) {
                $('.btn-phone-popover').popover('hide');
            }
        });
    });

    // Для карточек, загруженных через AJAX
    $(document).on('cards:loaded', function () { initPhonePopovers(); });
}(jQuery));
</script>
@endpushOnce