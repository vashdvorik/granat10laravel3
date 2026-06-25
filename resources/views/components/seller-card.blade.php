@php
    $sellerName = trim($seller->name ?? 'Магазин', '" ');
    $initial = mb_strtoupper(mb_substr($sellerName, 0, 1));
    $location = $seller->settlement?->name ?: $seller->town;
@endphp

<a class="shop-card" href="{{ $regionalUrl->route('seller', ['alias' => $seller->alias]) }}">
    <span class="shop-card__top">
        <span class="shop-logo">
            @if($seller->photo)
                <img src="{{ Storage::url($seller->photo) }}" alt="Логотип магазина {{ $sellerName }}"
                    width="176" height="176" loading="lazy"
                    onerror="this.style.display='none';this.parentElement.textContent='{{ $initial }}';">
            @else
                {{ $initial }}
            @endif
        </span>
        <span>
            <h2>{{ $sellerName }}</h2>
            <span class="shop-card__meta">
                @if($location)<span><i class="fa fa-map-marker" aria-hidden="true"></i> {{ $location }}</span>@endif
                <span>{{ $seller->products_count ?? 0 }} {{ trans_choice('товар|товара|товаров', $seller->products_count ?? 0) }}</span>
            </span>
        </span>
    </span>

    <span class="shop-card__description">
        {{ $seller->description ?: 'Посмотрите товары, контакты и актуальную информацию магазина.' }}
    </span>

    <span class="shop-card__footer">
        <span>{{ $seller->address ?: 'Адрес уточняйте у продавца' }}</span>
        <strong>Открыть <i class="fa fa-arrow-right" aria-hidden="true"></i></strong>
    </span>
</a>
