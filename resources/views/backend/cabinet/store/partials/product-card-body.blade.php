@php($productCabinetState = $productCabinetState ?? $product->cabinet_state)

<a href="{{ route('account.store.products.show', $product->id) }}">
    <h6 class="card-title seller-product-card__title">{{ $product->name }}</h6>
</a>

@if($product->category)
    <small class="text-muted d-block mb-2 seller-product-card__meta">
        <i class="uil uil-tag-alt"></i> {{ $product->category->name }}
    </small>
@endif

<div class="seller-product-card__state seller-product-card__state--{{ $productCabinetState->color }}">
    <div class="seller-product-card__state-title">
        <i class="uil uil-shield-check"></i>
        {{ $productCabinetState->label }}
    </div>
    <div class="seller-product-card__state-text">
        {{ \Illuminate\Support\Str::limit($productCabinetState->description, 120) }}
    </div>
</div>

@if($product->push_at && $product->view_state->isPublic)
    <small class="text-muted d-block mb-2">
        <i class="uil uil-clock"></i> Поднятие: {{ $product->push_at->format('d.m.Y в H:i') }}
    </small>
@endif

<div class="product-item__footer mb-3">
    <div class="d-flex align-items-center flex-wrap">
        @if($product->lowprice && $product->lowprice > 0)
            <span class="product-desc-price">{{ number_format($product->lowprice, 0) }} руб.</span>
            <span class="product-price">{{ number_format($product->price, 0) }} руб.</span>
            <span class="product-discount">
                -{{ round((($product->price - $product->lowprice) / $product->price) * 100) }}%
            </span>
        @else
            <span class="product-desc-price">{{ number_format($product->price, 0) }} руб.</span>
        @endif
    </div>
</div>

<div class="d-flex mt-20 flex-wrap gap-2 seller-product-card__actions">
    <div class="flex-fill">
        <x-product-moderation-actions :product="$product" />
    </div>

    @if($product->view_state->primaryAction !== \App\Enums\ProductPrimaryAction::Edit)
        <a href="{{ route('account.store.products.edit', $product->id) }}"
           class="btn btn-primary btn-default btn-squared border-0 flex-fill">
            Изменить
        </a>
    @endif
</div>
