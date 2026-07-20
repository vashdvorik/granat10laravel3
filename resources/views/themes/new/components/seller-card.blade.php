<a class="bimm-seller" href="{{ $regionalUrl->route('seller', ['alias' => $seller->alias]) }}">
    <span class="bimm-seller__logo">@if($seller->photo)<img src="{{ asset('storage/'.ltrim($seller->photo, '/')) }}" alt="{{ $seller->name }}">@else{{ mb_strtoupper(mb_substr(trim($seller->name, '" '), 0, 1)) }}@endif</span>
    <span><strong>{{ trim($seller->name, '" ') }}</strong><small>{{ $seller->settlement?->name ?: $seller->town }} · {{ $seller->products_count ?? $seller->product_count ?? 0 }} товаров</small></span>
</a>
