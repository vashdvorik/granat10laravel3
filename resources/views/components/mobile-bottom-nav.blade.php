@php
    $mobileBottomRegionalUrl = $regionalUrl ?? null;
    $mobileBottomRoute = static function (string $name) use ($mobileBottomRegionalUrl): string {
        return $mobileBottomRegionalUrl
            ? $mobileBottomRegionalUrl->route($name)
            : route($name);
    };

    $mobileBottomItems = [
        [
            'url' => $mobileBottomRoute('home'),
            'active' => request()->routeIs('home', 'regional.home'),
            'title' => 'Главная',
            'icon' => 'fa fa-home f-18',
            'label' => 'Главная',
        ],
        [
            'url' => $mobileBottomRoute('catalog.index'),
            'active' => request()->routeIs('catalog.*', 'regional.catalog.*'),
            'title' => 'Каталог',
            'icon' => 'fa fa-th f-18',
            'label' => 'Каталог',
        ],
        [
            'url' => $mobileBottomRoute('search'),
            'active' => request()->routeIs('search', 'regional.search'),
            'title' => 'Поиск',
            'icon' => 'fa fa-search f-18',
            'label' => 'Поиск',
        ],
        [
            'url' => $mobileBottomRoute('favorites'),
            'active' => request()->routeIs('favorites', 'regional.favorites'),
            'title' => 'Избранное',
            'icon' => 'fa fa-heart f-18',
            'label' => 'Избранное',
        ],
        [
            'url' => $mobileBottomRoute('shops'),
            'active' => request()->routeIs('shops', 'seller', 'regional.shops', 'regional.seller'),
            'title' => 'Магазины',
            'image' => asset('img/icon-ship.png'),
            'label' => 'Магазины',
        ],
    ];
@endphp

@once
    <style>
        .mobile-bottom-navbar {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 1px solid #e0e0e0;
            z-index: 1000;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
        }

        .mobile-navbar-wrapper {
            display: flex;
            justify-content: space-around;
            align-items: center;
            height: 60px;
            max-width: 100%;
        }

        .mobile-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-decoration: none;
            color: #666;
            font-size: 12px;
            transition: color 0.3s ease, background 0.3s ease;
        }

        .mobile-nav-item:hover,
        .mobile-nav-item:active,
        .mobile-nav-item.active {
            color: #ff6b6b;
            background: #f5f5f5;
        }

        .mobile-nav-item i {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .mobile-nav-label {
            font-size: 11px;
            white-space: nowrap;
        }

        @media (max-width: 767px) {
            .mobile-bottom-navbar {
                display: block;
            }

            body {
                padding-bottom: 60px;
            }
        }
    </style>
@endonce

<div class="mobile-bottom-navbar">
    <div class="mobile-navbar-wrapper">
        @foreach($mobileBottomItems as $item)
            <a href="{{ $item['url'] }}" class="mobile-nav-item {{ $item['active'] ? 'active' : '' }}" title="{{ $item['title'] }}">
                @if(isset($item['image']))
                    <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" style="width:20px; height:20px;">
                @else
                    <i class="{{ $item['icon'] }}"></i>
                @endif
                <span class="mobile-nav-label">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
