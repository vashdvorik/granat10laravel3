@php($productCabinetState = $productCabinetState ?? $product->cabinet_state)

<div class="card product product--grid position-relative seller-product-card"
     data-product-id="{{ $product->id }}"
     data-card-state="{{ $productCabinetState->status }}"
     data-card-signature="{{ $product->cabinet_state_signature }}">
    <div class="position-absolute seller-product-card__menu">
        <x-product-moderation-actions :product="$product" compact />
    </div>

    <div class="h-100">
        <div class="product-item">
            <div class="product-item__image seller-product-card__media">
                <div class="position-absolute top-0 start-0 m-2 d-flex gap-2 seller-product-card__badges">
                    @include('backend.cabinet.store.partials.product-card-badge', [
                        'product' => $product,
                        'productCabinetState' => $productCabinetState,
                    ])
                </div>

                <a href="{{ route('account.store.products.show', $product->id) }}">
                    @if($product->images->where('is_main', true)->first())
                        <img class="card-img-top img-fluid"
                             src="{{ asset('storage/' . $product->images->where('is_main', true)->first()->path) }}"
                             alt="{{ $product->name }}">
                    @elseif($product->images->first())
                        <img class="card-img-top img-fluid"
                             src="{{ asset('storage/' . $product->images->first()->path) }}"
                             alt="{{ $product->name }}">
                    @else
                        <div class="no-image-placeholder">
                            <i class="uil uil-image-slash"></i>
                            <p>Нет фото</p>
                        </div>
                    @endif
                </a>
            </div>

            <div class="card-body px-20 pb-25 pt-25 seller-product-card__body">
                <div class="product-item__body">
                    @include('backend.cabinet.store.partials.product-card-body', [
                        'product' => $product,
                        'productCabinetState' => $productCabinetState,
                    ])
                </div>
            </div>
        </div>
    </div>
</div>
