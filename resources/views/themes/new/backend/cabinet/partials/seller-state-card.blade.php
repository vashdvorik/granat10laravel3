@php
    $stateActionUrl = match ($sellerCabinetState->primaryAction) {
        'create_store' => route('account.store.create'),
        'choose_package' => route('account.packages.index'),
        'open_products', 'choose_visible_products', 'remove_extra_vip' => route('account.store.products.index'),
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
            <div class="col-6 col-lg-3">
                <small class="text-muted d-block">Тариф</small>
                <strong>{{ $sellerCabinetState->packageName ?? 'Нет тарифа' }}</strong>
            </div>
            <div class="col-6 col-lg-3">
                <small class="text-muted d-block">Видны покупателям</small>
                <strong>{{ $sellerCabinetState->visibleProductsCount }}</strong>
            </div>
            <div class="col-6 col-lg-3">
                <small class="text-muted d-block">Активные товары</small>
                <strong>
                    {{ $sellerCabinetState->activeProductsCount }}
                    @if($sellerCabinetState->activeProductsLimit !== null)
                        / {{ $sellerCabinetState->activeProductsLimit }}
                    @endif
                </strong>
            </div>
            <div class="col-6 col-lg-3">
                <small class="text-muted d-block">VIP</small>
                <strong>
                    {{ $sellerCabinetState->vipProductsCount }}
                    @if($sellerCabinetState->vipProductsLimit !== null)
                        / {{ $sellerCabinetState->vipProductsLimit }}
                    @endif
                </strong>
            </div>
        </div>

            @if(! $sellerCabinetState->packageIsLifetime && $sellerCabinetState->packageDaysLeft !== null && $sellerCabinetState->packageDaysLeft <= 7)
                <div class="seller-cabinet-renewal-note seller-cabinet-renewal-note--{{ $sellerCabinetState->autoRenewEnabled && $sellerCabinetState->autoRenewPossible ? 'success' : 'warning' }} mt-3">
                    @if($sellerCabinetState->autoRenewEnabled && $sellerCabinetState->autoRenewPossible)
                        Тариф продлится автоматически. После списания товары останутся видимыми.
                    @elseif($sellerCabinetState->autoRenewEnabled)
                        Автопродление включено, но баланса не хватает. При непродлении товары будут скрыты из каталога.
                    @else
                        Автопродление отключено. После окончания тарифа товары будут скрыты из каталога.
                    @endif
                </div>
            @endif

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
