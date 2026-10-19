{{-- Скрытые формы для переключателя города в шапке (components.region-switcher). Подключаются один раз на странице,
     вне любых других форм. Возврат на ту же страницу в другом городе делает RegionalSelectionController. --}}
@php
    $regions = $regionalContext->availableRegions();
@endphp

@if(config('regionality.enabled') && $regions->isNotEmpty())
    <div class="region-switch-forms" hidden>
        <form id="region-switch-all" method="POST" action="{{ route('regionality.update') }}">
            @csrf
            <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
        </form>
        @foreach($regions as $region)
            @foreach($region->settlements as $settlement)
                <form id="region-switch-{{ $settlement->id }}" method="POST" action="{{ route('regionality.update') }}">
                    @csrf
                    <input type="hidden" name="settlement_id" value="{{ $settlement->id }}">
                    <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                </form>
            @endforeach
        @endforeach
    </div>
@endif
