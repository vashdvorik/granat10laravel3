@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
@php
    $statusTone = match ($order->status) {
        \App\Enums\OrderStatus::New => 'new',
        \App\Enums\OrderStatus::Accepted,
        \App\Enums\OrderStatus::Processing => 'active',
        \App\Enums\OrderStatus::ReadyForPickup,
        \App\Enums\OrderStatus::OutForDelivery => 'progress',
        \App\Enums\OrderStatus::Completed => 'done',
        \App\Enums\OrderStatus::Cancelled => 'cancelled',
    };
    $statusOptions = [
        [
            'status' => \App\Enums\OrderStatus::New,
            'tone' => 'new',
            'hint' => 'Только что создан',
        ],
        [
            'status' => \App\Enums\OrderStatus::Accepted,
            'tone' => 'active',
            'hint' => 'Заказ принят в работу',
        ],
        [
            'status' => \App\Enums\OrderStatus::Processing,
            'tone' => 'active',
            'hint' => 'Сейчас обрабатывается',
        ],
        [
            'status' => \App\Enums\OrderStatus::ReadyForPickup,
            'tone' => 'progress',
            'hint' => 'Можно выдавать покупателю',
        ],
        [
            'status' => \App\Enums\OrderStatus::OutForDelivery,
            'tone' => 'progress',
            'hint' => 'Передан в доставку',
        ],
        [
            'status' => \App\Enums\OrderStatus::Completed,
            'tone' => 'done',
            'hint' => 'Заказ закрыт',
        ],
        [
            'status' => \App\Enums\OrderStatus::Cancelled,
            'tone' => 'cancelled',
            'hint' => 'Заказ отменён',
        ],
    ];
@endphp

<div class="contents orders-page order-show-page">
    <div class="container-fluid">
        <div class="order-show-hero">
            <div>
                <span class="orders-hero__eyebrow">Заказ магазина</span>
                <h4>{{ $order->number }}</h4>
                <p>Этот заказ остаётся доступным в кабинете, даже если приём новых заказов выключен.</p>
            </div>
            <div class="order-show-hero__actions">
                <span class="order-status-badge order-status-badge--{{ $statusTone }}">{{ $order->status->label() }}</span>
                <a href="{{ route('account.store.orders.index') }}" class="btn btn-sm btn-outline-primary">К списку</a>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="order-intake-card order-intake-card--{{ $orderIntakeStatus['enabled'] ? 'enabled' : 'disabled' }}">
            <div>
                <span>{{ $orderIntakeStatus['enabled'] ? 'Новые заказы включены' : 'Новые заказы ограничены' }}</span>
                <h5>{{ $orderIntakeStatus['label'] }}</h5>
                <p>{{ $orderIntakeStatus['description'] }}</p>
            </div>
            <a href="{{ $orderIntakeStatus['action'] }}" class="btn btn-sm {{ $orderIntakeStatus['enabled'] ? 'btn-outline-primary' : 'btn-primary' }}">
                {{ $orderIntakeStatus['action_label'] }}
            </a>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="order-detail-card">
                    <div class="order-detail-card__header">
                        <h6>Состав заказа</h6>
                    </div>
                    <div class="order-detail-card__body">
                        <div class="order-detail-row">
                            <span>Товар</span>
                            <strong>{{ $order->product_name }}</strong>
                        </div>
                        <div class="order-detail-row">
                            <span>Цена</span>
                            <strong>{{ number_format((float) $order->unit_price, 2, ',', ' ') }} руб.</strong>
                        </div>
                        <div class="order-detail-row">
                            <span>Количество</span>
                            <strong>{{ $order->quantity }} шт.</strong>
                        </div>
                        <div class="order-detail-row order-detail-row--total">
                            <span>Итого</span>
                            <strong>{{ number_format((float) $order->unit_price * $order->quantity, 2, ',', ' ') }} руб.</strong>
                        </div>
                    </div>
                </div>

                <div class="order-detail-card">
                    <div class="order-detail-card__header">
                        <h6>Покупатель и получение</h6>
                    </div>
                    <div class="order-detail-card__body">
                        <div class="order-detail-row">
                            <span>Имя</span>
                            <strong>{{ $order->customer_name }}</strong>
                        </div>
                        <div class="order-detail-row">
                            <span>Телефон</span>
                            <strong><a href="tel:{{ preg_replace('/\D+/', '', $order->customer_phone) }}">{{ $order->customer_phone }}</a></strong>
                        </div>
                        <div class="order-detail-row">
                            <span>Способ получения</span>
                            <strong>{{ $order->isDelivery() ? 'Доставка' : 'Самовывоз' }}</strong>
                        </div>
                        @if($order->isDelivery())
                            <div class="order-detail-row">
                                <span>Адрес</span>
                                <strong>{{ $order->delivery_address }}</strong>
                            </div>
                        @endif
                        @if($order->customer_comment)
                            <div class="order-detail-row">
                                <span>Комментарий</span>
                                <strong>{{ $order->customer_comment }}</strong>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="order-detail-card">
                    <div class="order-detail-card__header">
                        <h6>Изменить статус</h6>
                    </div>
                    <div class="order-detail-card__body">
                        <form method="POST" action="{{ route('account.store.orders.status', $order) }}">
                            @csrf
                            @method('PATCH')
                            <div class="order-status-picker-label">
                                Выберите новый статус
                            </div>
                            <div class="order-status-picker">
                                @foreach($statusOptions as $option)
                                    <button type="submit"
                                            name="status"
                                            value="{{ $option['status']->value }}"
                                            class="order-status-option order-status-option--{{ $option['tone'] }} {{ $order->status === $option['status'] ? 'is-active' : '' }}">
                                        <span class="order-status-option__title">{{ $option['status']->label() }}</span>
                                        <span class="order-status-option__hint">{{ $option['hint'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <label for="comment" class="form-label">Комментарий</label>
                            <textarea id="comment" name="comment" class="form-control mb-3" rows="3" maxlength="1000"></textarea>
                            <div class="order-status-picker__note">
                                Нажмите на нужный статус. Комментарий добавится вместе с ним.
                            </div>
                        </form>
                    </div>
                </div>

                <div class="order-detail-card">
                    <div class="order-detail-card__header">
                        <h6>История статусов</h6>
                    </div>
                    <div class="order-detail-card__body">
                        <div class="order-history">
                            @forelse($order->statusHistory->sortByDesc('created_at') as $entry)
                                <div class="order-history__item">
                                    <strong>{{ $entry->to_status->label() }}</strong>
                                    <span>{{ $entry->created_at->format('d.m.Y H:i') }}</span>
                                    @if($entry->comment)
                                        <p>{{ $entry->comment }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="mb-0 text-muted">История появится после первого изменения статуса.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
