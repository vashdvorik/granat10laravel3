@php
    $stateActionUrl = match ($sellerCabinetState->primaryAction) {
        'create_store' => route('account.store.create'),
        'open_products' => route('account.store.products.index'),
        'add_product' => route('account.store.products.create'),
        'edit_store' => isset($seller) ? route('account.store.edit', $seller->id) : null,
        'open_store' => isset($seller) ? route('seller', $seller->alias) : null,
        default => null,
    };
@endphp

<div class="card mb-25 seller-cabinet-state">
    <div class="card-body">
        @php
            $showPrimaryAction = $showPrimaryAction ?? true;
        @endphp
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <span class="badge badge-round badge-{{ $sellerCabinetState->alertType }} mb-2">
                    {{ $sellerCabinetState->label }}
                </span>
                <p class="mb-0 text-muted">{{ $sellerCabinetState->description }}</p>
            </div>

            @if($showPrimaryAction && $stateActionUrl && $sellerCabinetState->primaryActionLabel)
                <a href="{{ $stateActionUrl }}" class="btn btn-primary btn-sm btn-squared">
                    {{ $sellerCabinetState->primaryActionLabel }}
                </a>
            @elseif($showPrimaryAction && $sellerCabinetState->primaryActionLabel)
                <span class="text-muted small">{{ $sellerCabinetState->primaryActionLabel }}: сценарий ещё не подключён</span>
            @endif
        </div>

        <div class="row g-3 mt-2">
            <div class="col-6 col-lg-4">
                <small class="text-muted d-block">Баланс</small>
                <strong>{{ number_format((float) $sellerCabinetState->balance, 2, ',', ' ') }} руб</strong>
            </div>
            <div class="col-6 col-lg-4">
                <small class="text-muted d-block">В каталоге</small>
                <strong>{{ $sellerCabinetState->visibleProductsCount }}</strong>
            </div>
            <div class="col-6 col-lg-4">
                <small class="text-muted d-block">VIP</small>
                <strong>{{ $sellerCabinetState->vipProductsCount }}</strong>
            </div>
        </div>

        @if($sellerCabinetState->blockers !== [])
            <div class="seller-cabinet-state__blockers">
                <h6>Что требует внимания</h6>
                <ul>
                    @foreach(array_slice($sellerCabinetState->blockers, 0, 3) as $blocker)
                        <li>{{ $blocker['message'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
