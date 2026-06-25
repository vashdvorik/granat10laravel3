@extends('main_layots')

@section('title', 'Заказ принят')

@section('meta')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<div class="container container-240">
    <div class="order-result">
        <h1>Заказ принят</h1>
        <p>Номер заказа: <strong>{{ $order->number }}</strong></p>
        <p>Товар: {{ $order->product_name }}</p>
        <p>Количество: {{ $order->quantity }}</p>
        <p>Сумма: <strong>{{ number_format((float) $order->unit_price * $order->quantity, 2, ',', ' ') }} руб.</strong></p>
        <p>Продавец свяжется с вами по указанному телефону.</p>
        <button type="button" onclick="window.print()">Распечатать</button>
        <a href="{{ route('catalog.index') }}">Вернуться в каталог</a>
    </div>
</div>

<style>
.order-result{max-width:680px;margin:40px auto;padding:28px;border:1px solid #e8e8e8;border-radius:8px;background:#fff}
.order-result h1{margin-top:0}.order-result button,.order-result a{display:inline-block;margin:14px 12px 0 0;padding:10px 16px}
@media print{header,footer,.order-result button,.order-result a{display:none!important}.order-result{border:0;margin:0;max-width:none}}
</style>
@endsection
