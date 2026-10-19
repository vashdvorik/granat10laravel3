{{-- Подсказка на главной: посетитель раньше выбирал город, но сейчас открыт общий адрес (все регионы).
     Город сам не включается: только предложение. «Не нужно» забывает выбор (cookie), чтобы подсказка не повторялась. --}}
@php
    $suggested = $regionalContext->suggestedSettlement();
@endphp

@if($suggested && Route::has('regional.home'))
    <div class="region-notice region-notice--suggestion" role="status">
        <i class="fa fa-map-marker" aria-hidden="true"></i>
        <span class="region-notice__text">В прошлый раз вы выбирали {{ $suggested->name }}.</span>
        <a class="region-notice__action" href="{{ route('regional.home', ['location' => $suggested->slug]) }}">Показать только {{ $suggested->name }}</a>
        <form method="POST" action="{{ route('regionality.update') }}" class="region-notice__form">
            @csrf
            <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
            <button type="submit" class="region-notice__dismiss">Не нужно</button>
        </form>
    </div>
@endif
