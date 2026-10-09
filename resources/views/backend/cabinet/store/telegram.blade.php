{{-- Telegram-уведомления о новых заказах: подключение и отключение бота (обе темы кабинета) --}}

@extends('backend.cabinet.layout_cabinet')

@section('title', 'Telegram-уведомления')

@section('content_cabinet')
<div class="contents telegram-notify-page">
    <div class="container-fluid">
        @include('backend.cabinet.partials.flash')

        <div class="row">
            <div class="col-12">
                <div class="breadcrumb-main">
                    <h4 class="breadcrumb-title">Telegram-уведомления</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="card mb-25 telegram-notify-card">
                    <div class="card-body">
                        @if($seller->telegram_verified_at)
                            <span class="badge badge-round badge-success mb-3">Подключено</span>
                            <p class="mb-2">
                                Telegram подключён {{ $seller->telegram_verified_at->format('d.m.Y H:i') }}.
                                @if($botUsername !== '')
                                    Бот: <strong>{{ '@'.$botUsername }}</strong>.
                                @endif
                            </p>
                            <p class="text-muted">Когда покупатель оформит заказ, бот сразу пришлёт вам сообщение.</p>

                            <form method="POST" action="{{ route('account.store.telegram.disconnect') }}"
                                  onsubmit="return confirm('Отключить Telegram? Сообщения о новых заказах приходить перестанут.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">Отключить Telegram</button>
                            </form>
                        @else
                            <span class="badge badge-round badge-warning mb-3">Не подключено</span>
                            <p class="mb-3">
                                Бот пришлёт вам сообщение сразу, когда покупатель оформит заказ: номер, товар, сумму,
                                способ получения, контакты покупателя и ссылку на заказ.
                            </p>

                            @if($botUsername === '')
                                <div class="alert alert-warning mb-0">
                                    Telegram-бот пока не настроен администратором сайта. Подключение станет доступно позже.
                                </div>
                            @else
                                <ol class="telegram-notify-steps mb-3">
                                    <li>Нажмите «Подключить Telegram»: откроется бот <strong>{{ '@'.$botUsername }}</strong>.</li>
                                    <li>В Telegram нажмите «Запустить» (Start).</li>
                                    <li>Бот ответит, что магазин подключён. Вернитесь сюда и нажмите «Обновить статус».</li>
                                </ol>
                                <p class="text-muted small">Ссылка для подключения действует 15 минут: если не успели, нажмите кнопку снова.</p>

                                <div class="d-flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('account.store.telegram.connect') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">Подключить Telegram</button>
                                    </form>
                                    <a href="{{ route('account.store.telegram') }}" class="btn btn-outline-primary btn-sm">Обновить статус</a>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card mb-25 telegram-notify-card">
                    <div class="card-body">
                        <h6 class="mb-3">Что приходит в Telegram</h6>
                        <ul class="mb-3 ps-3" style="list-style: disc;">
                            <li>Сообщение о <strong>каждом новом заказе</strong>.</li>
                            <li>Сообщений о смене статуса заказа нет: статусы вы меняете сами в разделе «Заказы».</li>
                        </ul>
                        <p class="text-muted small mb-0">
                            Уведомления в кабинете (колокольчик вверху) работают независимо от Telegram.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
