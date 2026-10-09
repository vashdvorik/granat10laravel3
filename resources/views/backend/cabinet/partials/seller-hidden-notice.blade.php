{{-- Красное уведомление в кабинете: магазин скрыт администратором. Причину пишет администратор; текст экранируется. --}}
@php
    $hiddenNoticeSeller = $seller ?? auth()->user()?->seller;
@endphp
@if($hiddenNoticeSeller && $hiddenNoticeSeller->isHidden())
    <div class="seller-hidden-notice" role="alert">
        <span class="seller-hidden-notice__icon" aria-hidden="true"><i class="uil uil-ban"></i></span>
        <div class="seller-hidden-notice__body">
            <h2 class="seller-hidden-notice__title">Ваш магазин скрыт администратором</h2>
            <p class="seller-hidden-notice__text">
                Покупатели не видят магазин и его товары: их нет в каталоге, поиске и на странице магазина.
                Заказы, которые уже есть, остаются в кабинете.
            </p>
            <div class="seller-hidden-notice__reason">
                <strong>Причина:</strong>
                {!! nl2br(e($hiddenNoticeSeller->hidden_reason)) !!}
            </div>
            <p class="seller-hidden-notice__next">
                Исправьте это в <a href="{{ route('account.store.edit', $hiddenNoticeSeller->id) }}">настройках магазина</a>
                и напишите в поддержку: после проверки магазин вернётся на сайт.
                @if($hiddenNoticeSeller->hidden_at)
                    Скрыт {{ $hiddenNoticeSeller->hidden_at->format('d.m.Y') }}.
                @endif
            </p>
        </div>
    </div>
@endif
