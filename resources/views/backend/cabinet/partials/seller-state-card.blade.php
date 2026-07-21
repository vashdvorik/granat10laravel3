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
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <span class="badge badge-round badge-{{ $sellerCabinetState->alertType }} mb-2">
                    {{ $sellerCabinetState->label }}
                </span>
                <p class="mb-0 text-muted">{{ $sellerCabinetState->description }}</p>
            </div>

            @if($stateActionUrl && $sellerCabinetState->primaryActionLabel)
                <a href="{{ $stateActionUrl }}" class="btn btn-primary btn-sm btn-squared">
                    {{ $sellerCabinetState->primaryActionLabel }}
                </a>
            @elseif($sellerCabinetState->primaryActionLabel)
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

        @if(($showRenewalDetails ?? true) && $sellerCabinetState->hasActivePackage)
            <div class="seller-cabinet-renewal mt-3">
                <div class="seller-cabinet-renewal__item">
                    <small>Срок тарифа</small>
                    <strong>
                        @if($sellerCabinetState->packageIsLifetime)
                            Бессрочно
                        @elseif($sellerCabinetState->packageEndsAt)
                            до {{ $sellerCabinetState->packageEndsAt->format('d.m.Y') }}
                            @if($sellerCabinetState->packageDaysLeft !== null)
                                <span>({{ max(0, $sellerCabinetState->packageDaysLeft) }} дн.)</span>
                            @endif
                        @else
                            Не указан
                        @endif
                    </strong>
                </div>

                @if(! $sellerCabinetState->packageIsLifetime)
                    <div class="seller-cabinet-renewal__item">
                        <small>Автопродление</small>
                        <strong class="{{ $sellerCabinetState->autoRenewEnabled ? 'text-success' : 'text-muted' }}">
                            {{ $sellerCabinetState->autoRenewEnabled ? 'Включено' : 'Выключено' }}
                        </strong>
                    </div>
                    <div class="seller-cabinet-renewal__item">
                        <small>Спишется при продлении</small>
                        <strong>{{ number_format((float) $sellerCabinetState->packageRenewalPrice, 0, '.', ' ') }} руб.</strong>
                    </div>
                    <div class="seller-cabinet-renewal__item">
                        <small>Баланс</small>
                        <strong class="{{ $sellerCabinetState->autoRenewPossible ? 'text-success' : 'text-danger' }}">
                            {{ number_format((float) $sellerCabinetState->balance, 0, '.', ' ') }} руб.
                        </strong>
                    </div>
                @endif
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
        @endif

        @if($sellerCabinetState->blockers !== [])
            <div class="mt-3 pt-3 border-top">
                <small class="text-muted d-block mb-2">Что требует внимания</small>
                <ul class="mb-0 ps-3">
                    @foreach(array_slice($sellerCabinetState->blockers, 0, 3) as $blocker)
                        <li>{{ $blocker['message'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
