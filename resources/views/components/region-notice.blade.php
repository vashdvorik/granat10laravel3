{{-- Плашка «вы видите только один город» с выходом в один клик. Показывается, пока город выбран (адрес /город/...).
     $total (необязательно): сколько всего нашлось на странице; если мало, плашка предлагает посмотреть все регионы. --}}
@php
    $settlement = $regionalContext->settlement();
    $total = isset($total) ? (int) $total : null;
    $fewResults = $total !== null && $total < 8;
@endphp

@if(config('regionality.enabled') && $settlement)
    <div class="region-notice {{ $fewResults ? 'region-notice--few' : '' }}" role="status">
        <i class="fa fa-map-marker" aria-hidden="true"></i>
        <span class="region-notice__text">
            @if($fewResults)
                {{ \Illuminate\Support\Str::ucfirst($settlement->inPhrase()) }} найдено {{ $total }} {{ \App\Support\Plural::ru($total, 'товар', 'товара', 'товаров') }}.
            @else
                Показаны товары и магазины только {{ $settlement->inPhrase() }}.
            @endif
        </span>
        <form method="POST" action="{{ route('regionality.update') }}" class="region-notice__form">
            @csrf
            <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
            <button type="submit" class="region-notice__action">Показать все регионы</button>
        </form>
    </div>
@endif
