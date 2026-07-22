@php
    $activePackage = $seller->activePackage();
    $package = $activePackage?->package;
    $canShowWebsite = $sellerCabinetState->canShowWebsite ?? (bool) ($package?->has_website_link ?? false);
    $canShowSocialLinks = $sellerCabinetState->canShowSocialLinks ?? (bool) ($package?->has_social_links ?? false);
    $canUseOrders = (bool) ($package?->has_order_system ?? false);

    $featureItems = [
        [
            'title' => 'Веб-сайт',
            'icon' => 'uil uil-globe',
            'value' => $seller->link,
            'url' => $seller->link,
            'filled' => filled($seller->link),
            'available' => $canShowWebsite,
            'always_available' => false,
            'empty' => 'Сайт не указан.',
            'blocked' => 'Сайт сохранён, но не показывается покупателям на текущем тарифе.',
        ],
        [
            'title' => 'Google Maps',
            'icon' => 'uil uil-map',
            'value' => $seller->link_map ? 'Открыть на карте' : null,
            'url' => $seller->link_map,
            'filled' => filled($seller->link_map),
            'available' => true,
            'always_available' => true,
            'empty' => 'Ссылка на карту не указана.',
            'blocked' => null,
        ],
        [
            'title' => 'Telegram',
            'icon' => 'uil uil-telegram',
            'value' => $seller->telegram,
            'url' => null,
            'filled' => filled($seller->telegram),
            'available' => $canShowSocialLinks,
            'always_available' => false,
            'empty' => 'Telegram не указан.',
            'blocked' => 'Telegram сохранён, но не показывается покупателям на текущем тарифе.',
        ],
        [
            'title' => 'Instagram',
            'icon' => 'uil uil-instagram',
            'value' => $seller->instagram,
            'url' => null,
            'filled' => filled($seller->instagram),
            'available' => $canShowSocialLinks,
            'always_available' => false,
            'empty' => 'Instagram не указан.',
            'blocked' => 'Instagram сохранён, но не показывается покупателям на текущем тарифе.',
        ],
        [
            'title' => 'Viber',
            'icon' => 'uil uil-phone-volume',
            'value' => $seller->viber,
            'url' => null,
            'filled' => filled($seller->viber),
            'available' => $canShowSocialLinks,
            'always_available' => false,
            'empty' => 'Viber не указан.',
            'blocked' => 'Viber сохранён, но не показывается покупателям на текущем тарифе.',
        ],
        [
            'title' => 'Заказы через сайт',
            'icon' => 'uil uil-shopping-bag',
            'value' => $seller->can_order ? 'Включены в настройках магазина' : null,
            'url' => null,
            'filled' => (bool) $seller->can_order,
            'available' => $canUseOrders,
            'always_available' => false,
            'empty' => $canUseOrders ? 'Доступно по тарифу, но выключено в настройках.' : 'Недоступно на текущем тарифе.',
            'blocked' => 'Настройка заказов сохранена, но текущий тариф не принимает новые заказы через сайт.',
        ],
    ];
@endphp

<div class="store-feature-grid">
    @foreach($featureItems as $feature)
        @php
            $isVisible = $feature['available'] && $feature['filled'];
            $isBlocked = ! $feature['available'] && $feature['filled'];
            $stateClass = $isVisible ? 'visible' : ($isBlocked ? 'blocked' : 'empty');
            $stateLabel = match ($stateClass) {
                'visible' => $feature['always_available'] ? 'Заполнено' : 'Показывается',
                'blocked' => 'Сохранено, скрыто тарифом',
                default => $feature['available'] ? 'Не заполнено' : 'Недоступно',
            };
        @endphp
        <div class="store-feature-card store-feature-card--{{ $stateClass }}">
            <div class="store-feature-card__icon">
                <i class="{{ $feature['icon'] }}"></i>
            </div>
            <div class="store-feature-card__body">
                <div class="store-feature-card__head">
                    <strong>{{ $feature['title'] }}</strong>
                    <span>{{ $stateLabel }}</span>
                </div>

                @if($feature['filled'])
                    @if($feature['url'])
                        <a href="{{ $feature['url'] }}" target="_blank" rel="noopener">
                            {{ \Illuminate\Support\Str::limit((string) $feature['value'], 34) }} →
                        </a>
                    @else
                        <p>{{ $feature['value'] }}</p>
                    @endif
                @else
                    <p>{{ $feature['empty'] }}</p>
                @endif

                @if($isBlocked)
                    <small>{{ $feature['blocked'] }}</small>
                @elseif(! $feature['always_available'] && ! $feature['available'])
                    <small>Данные можно заполнить, но покупатели увидят их только после смены тарифа.</small>
                @endif
            </div>
        </div>
    @endforeach
</div>
