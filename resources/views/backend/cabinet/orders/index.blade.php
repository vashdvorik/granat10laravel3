@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
<div class="contents">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-25">
            <h4 class="mb-0">Заказы</h4>
            <form method="GET" action="{{ route('account.store.orders.index') }}">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">Все статусы</option>
                    @foreach(\App\Enums\OrderStatus::cases() as $orderStatus)
                        <option value="{{ $orderStatus->value }}" @selected($status === $orderStatus->value)>
                            {{ $orderStatus->label() }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-body p-0">
                @forelse($orders as $order)
                    <a href="{{ route('account.store.orders.show', $order) }}"
                       class="d-block p-3 border-bottom text-dark text-decoration-none">
                        <div class="d-flex justify-content-between gap-3 flex-wrap">
                            <div>
                                <strong>{{ $order->number }}</strong>
                                <div>{{ $order->product_name }} × {{ $order->quantity }}</div>
                                <small class="text-muted">{{ $order->customer_name }} · {{ $order->customer_phone }}</small>
                            </div>
                            <div class="text-md-end">
                                <span class="badge badge-round badge-primary">{{ $order->status->label() }}</span>
                                <div class="fw-600 mt-1">
                                    {{ number_format((float) $order->unit_price * $order->quantity, 2, ',', ' ') }} руб.
                                </div>
                                <small class="text-muted">{{ $order->created_at->format('d.m.Y H:i') }}</small>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-4 text-center text-muted">Заказов пока нет.</div>
                @endforelse
            </div>
        </div>

        @if($orders->hasPages())
            <div class="mt-3">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
