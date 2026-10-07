@extends('main_layots')

@section('title', 'Заказ '.$order->number.' — BIMM')

@section('content')
<div class="bimm-empty bimm-order-success" style="max-width:720px;margin:30px auto;text-align:left"><span class="bimm-kicker">Заявка отправлена</span><h1 style="margin:0 0 20px;font-size:34px">Спасибо, продавец получил ваш заказ</h1><p>Номер заказа: <strong>{{ $order->number }}</strong></p><p>Товар: <strong>{{ $order->product_name }}</strong></p><p>Количество: {{ $order->quantity }}</p><p>Сумма: <strong>{{ number_format((float)$order->unit_price * $order->quantity, 0, '.', ' ') }} ₽</strong></p><p>Продавец свяжется с вами по указанному телефону, чтобы подтвердить детали.</p><div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:22px"><a class="bimm-button bimm-button--dark" href="{{ route('orders.index') }}">Посмотреть мои заказы</a><a class="bimm-button bimm-button--light" href="{{ $regionalUrl->route('catalog.index') }}">Продолжить покупки</a><button class="bimm-button bimm-button--light" type="button" onclick="window.print()">Распечатать</button></div></div>
@endsection
