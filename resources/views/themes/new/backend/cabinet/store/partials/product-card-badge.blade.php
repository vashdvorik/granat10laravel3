@php($productCabinetState = $productCabinetState ?? $product->cabinet_state)

@if($product->is_vip)
    <span class="seller-product-card__badge seller-product-card__badge--vip">
        <i class="uil uil-star"></i> VIP
    </span>
@endif

@if($productCabinetState->visibleToBuyers)
    <span class="seller-product-card__badge seller-product-card__badge--success">
        <i class="uil uil-check"></i> Виден
    </span>
@else
    <span class="seller-product-card__badge seller-product-card__badge--muted">
        <i class="uil uil-pause"></i> {{ $productCabinetState->label }}
    </span>
@endif
