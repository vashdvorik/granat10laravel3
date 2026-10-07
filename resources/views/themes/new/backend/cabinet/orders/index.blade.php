@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
@php
    $statusTone = fn (\App\Enums\OrderStatus $orderStatus): string => match ($orderStatus) {
        \App\Enums\OrderStatus::New => 'new',
        \App\Enums\OrderStatus::Accepted,
        \App\Enums\OrderStatus::Processing => 'active',
        \App\Enums\OrderStatus::ReadyForPickup,
        \App\Enums\OrderStatus::OutForDelivery => 'progress',
        \App\Enums\OrderStatus::Completed => 'done',
        \App\Enums\OrderStatus::Cancelled => 'cancelled',
    };
@endphp

<div class="contents orders-page">
    <div class="container-fluid">
        <div class="cabinet-page-title">
            <div>
                <p class="eyebrow">Продажи</p>
                <h1>Заказы покупателей</h1>
                <p>Отвечайте на заявки, согласуйте получение и держите покупателя в курсе.</p>
            </div>
        </div>
        <div class="order-intake-card order-intake-card--{{ $orderIntakeStatus['enabled'] ? 'enabled' : 'disabled' }}">
            <div>
                <span>{{ $orderIntakeStatus['enabled'] ? 'Приём включён' : 'Приём ограничен' }}</span>
                <h5>{{ $orderIntakeStatus['label'] }}</h5>
                <p>{{ $orderIntakeStatus['description'] }}</p>
            </div>
            <a href="{{ $orderIntakeStatus['action'] }}" class="btn btn-sm {{ $orderIntakeStatus['enabled'] ? 'btn-outline-primary' : 'btn-primary' }}">
                {{ $orderIntakeStatus['action_label'] }}
            </a>
        </div>


        <div class="orders-toolbar">
            <div>
                <strong>Фильтр по статусу</strong>
                <span>Показываем только заказы этого магазина.</span>
            </div>
            <div class="orders-status-filter">
                <a href="{{ route('account.store.orders.index') }}" class="{{ $status === '' ? 'is-active' : '' }}">
                    Все
                    <span>{{ $totalOrders }}</span>
                </a>
                @foreach(\App\Enums\OrderStatus::cases() as $orderStatus)
                    <a href="{{ route('account.store.orders.index', ['status' => $orderStatus->value]) }}"
                       class="{{ $status === $orderStatus->value ? 'is-active' : '' }}">
                        {{ $orderStatus->label() }}
                        <span>{{ (int) ($statusCounts[$orderStatus->value] ?? 0) }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="orders-list">
            @forelse($orders as $order)
                <a href="{{ route('account.store.orders.show', $order) }}" class="order-list-card">
                    <div class="order-list-card__main">
                        <div class="order-list-card__top">
                            <strong>{{ $order->number }}</strong>
                            <span class="order-status-badge order-status-badge--{{ $statusTone($order->status) }}">
                                {{ $order->status->label() }}
                            </span>
                        </div>
                        <h5>{{ $order->product_name }}</h5>
                        <p>{{ $order->customer_name }} · {{ $order->customer_phone }}</p>
                    </div>
                    <div class="order-list-card__meta">
                        <strong>{{ number_format((float) $order->unit_price * $order->quantity, 2, ',', ' ') }} руб.</strong>
                        <span>{{ $order->quantity }} шт.</span>
                        <small>{{ $order->created_at->format('d.m.Y H:i') }}</small>
                    </div>
                </a>
            @empty
                <div class="orders-empty">
                    <span class="uil uil-receipt"></span>
                    <h5>Заказов пока нет</h5>
                    <p>Когда покупатели оформят заказ через сайт, он появится здесь. Если приём заказов отключён, история уже созданных заказов всё равно сохранится.</p>
                </div>
            @endforelse
        </div>

        @if($orders->hasPages())
            <div class="mt-3">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
