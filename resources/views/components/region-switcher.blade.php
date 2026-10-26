{{-- Выбор города в шапке на компьютере (в строке поиска вместо выбора категории). На телефоне отдельной строки с выбором
     нет: там, когда город выбран, видна плашка components.region-notice с кнопкой сброса, а выбрать город можно в
     фильтрах каталога и на странице «Магазины». Фильтр никогда не бывает «невидимым».
     Пункты списка — кнопки, привязанные атрибутом form к скрытым формам из components.region-switch-forms:
     шапка с поиском сама находится внутри формы, а вложенные формы в HTML недопустимы. --}}
@php
    $regions = $regionalContext->availableRegions();
    $variant = $variant ?? 'header';
@endphp

@if(config('regionality.enabled') && $regions->isNotEmpty())
    <div class="region-switcher region-switcher--{{ $variant }}">
        <button class="region-switcher__toggle dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Выбрать город: сейчас «{{ $regionalContext->label() }}»">
            <i class="fa fa-map-marker" aria-hidden="true"></i>
            <span class="region-switcher__label">{{ $regionalContext->label() }}</span>
            <i class="fa fa-caret-down" aria-hidden="true"></i>
        </button>
        <ul class="dropdown-menu region-switcher__menu">
            <li>
                <button type="submit" form="region-switch-all" class="region-switcher__option {{ $regionalContext->isAllRegions() ? 'is-active' : '' }}">
                    Все регионы
                </button>
            </li>
            @foreach($regions as $region)
                <li class="region-switcher__group">{{ $region->name }}</li>
                @foreach($region->settlements as $settlement)
                    <li>
                        <button type="submit" form="region-switch-{{ $settlement->id }}" class="region-switcher__option {{ $regionalContext->settlement()?->is($settlement) ? 'is-active' : '' }}">
                            {{ $settlement->name }}
                        </button>
                    </li>
                @endforeach
            @endforeach
        </ul>
    </div>
@endif
