@php
    $featureItems = [
        [
            'title' => 'Веб-сайт',
            'icon' => 'uil uil-globe',
            'value' => $seller->link,
            'url' => $seller->link,
            'filled' => filled($seller->link),
            'empty' => 'Сайт не указан.',
        ],
        [
            'title' => 'Google Maps',
            'icon' => 'uil uil-map',
            'value' => $seller->link_map ? 'Открыть на карте' : null,
            'url' => $seller->link_map,
            'filled' => filled($seller->link_map),
            'empty' => 'Ссылка на карту не указана.',
        ],
        [
            'title' => 'Telegram',
            'icon' => 'uil uil-telegram',
            'value' => $seller->telegram,
            'url' => null,
            'filled' => filled($seller->telegram),
            'empty' => 'Telegram не указан.',
        ],
        [
            'title' => 'Instagram',
            'icon' => 'uil uil-instagram',
            'value' => $seller->instagram,
            'url' => null,
            'filled' => filled($seller->instagram),
            'empty' => 'Instagram не указан.',
        ],
        [
            'title' => 'Viber',
            'icon' => 'uil uil-phone-volume',
            'value' => $seller->viber,
            'url' => null,
            'filled' => filled($seller->viber),
            'empty' => 'Viber не указан.',
        ],
        [
            'title' => 'Заказы через сайт',
            'icon' => 'uil uil-shopping-bag',
            'value' => $seller->can_order ? 'Включены в настройках магазина' : null,
            'url' => null,
            'filled' => (bool) $seller->can_order,
            'empty' => 'Выключены в настройках магазина.',
        ],
    ];
@endphp

<div class="store-feature-grid">
    @foreach($featureItems as $feature)
        @php
            $stateClass = $feature['filled'] ? 'visible' : 'empty';
            $stateLabel = $feature['filled'] ? 'Показывается' : 'Не заполнено';
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
            </div>
        </div>
    @endforeach
</div>
