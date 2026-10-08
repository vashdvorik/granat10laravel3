@extends('main_layots')

@section('title', 'Заказ '.$order->number.' — BIMM')

@section('content')
<div class="bimm-empty bimm-order-success" style="max-width:720px;margin:30px auto;text-align:left">
    <span class="bimm-kicker">Заявка отправлена</span>
    <h1 style="margin:0 0 8px;font-size:34px">Спасибо, продавец получил ваш заказ</h1>
    <p style="margin:0 0 20px;color:var(--bimm-muted)">Продавец скоро свяжется с вами, чтобы подтвердить детали.</p>

    @include('orders._result')
</div>
@endsection
