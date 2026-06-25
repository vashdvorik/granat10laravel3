@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
<div class="contents">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-25">
            <div>
                <h4 class="mb-1">Заказ {{ $order->number }}</h4>
                <span class="badge badge-round badge-primary">{{ $order->status->label() }}</span>
            </div>
            <a href="{{ route('account.store.orders.index') }}" class="btn btn-sm btn-secondary">К списку</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row">
            <div class="col-lg-7">
                <div class="card mb-25">
                    <div class="card-header"><h6 class="mb-0">Состав заказа</h6></div>
                    <div class="card-body">
                        <p><strong>Товар:</strong> {{ $order->product_name }}</p>
                        <p><strong>Цена:</strong> {{ number_format((float) $order->unit_price, 2, ',', ' ') }} руб.</p>
                        <p><strong>Количество:</strong> {{ $order->quantity }}</p>
                        <p class="mb-0"><strong>Итого:</strong>
                            {{ number_format((float) $order->unit_price * $order->quantity, 2, ',', ' ') }} руб.
                        </p>
                    </div>
                </div>

                <div class="card mb-25">
                    <div class="card-header"><h6 class="mb-0">Покупатель</h6></div>
                    <div class="card-body">
                        <p><strong>Имя:</strong> {{ $order->customer_name }}</p>
                        <p><strong>Телефон:</strong> <a href="tel:{{ preg_replace('/\D+/', '', $order->customer_phone) }}">{{ $order->customer_phone }}</a></p>
                        <p><strong>Получение:</strong> {{ $order->isDelivery() ? 'Доставка' : 'Самовывоз' }}</p>
                        @if($order->isDelivery())
                            <p><strong>Адрес:</strong> {{ $order->delivery_address }}</p>
                        @endif
                        @if($order->customer_comment)
                            <p class="mb-0"><strong>Комментарий:</strong> {{ $order->customer_comment }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                @if(!$order->isFinal())
                    <div class="card mb-25">
                        <div class="card-header"><h6 class="mb-0">Изменить статус</h6></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('account.store.orders.status', $order) }}">
                                @csrf
                                @method('PATCH')
                                <label for="status" class="form-label">Следующий статус</label>
                                <select id="status" name="status" class="form-control mb-3" required>
                                    @foreach($order->status->allowedTransitions() as $nextStatus)
                                        <option value="{{ $nextStatus->value }}">{{ $nextStatus->label() }}</option>
                                    @endforeach
                                </select>
                                <label for="comment" class="form-label">Комментарий</label>
                                <textarea id="comment" name="comment" class="form-control mb-3" rows="3" maxlength="1000"></textarea>
                                <button type="submit" class="btn btn-primary w-100">Сохранить статус</button>
                            </form>
                        </div>
                    </div>
                @endif

                <div class="card mb-25">
                    <div class="card-header"><h6 class="mb-0">История</h6></div>
                    <div class="card-body">
                        @foreach($order->statusHistory->sortByDesc('created_at') as $entry)
                            <div class="border-bottom pb-2 mb-2">
                                <strong>{{ $entry->to_status->label() }}</strong>
                                <div class="small text-muted">{{ $entry->created_at->format('d.m.Y H:i') }}</div>
                                @if($entry->comment)<div class="mt-1">{{ $entry->comment }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
