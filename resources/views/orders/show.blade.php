@extends('main_layots')

@section('title', 'Заказ принят — BIMM')

@section('meta')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<div class="container container-240">
    <div class="order-result">
        <h1>Заказ принят</h1>
        <p class="order-result__lead">Продавец получил вашу заявку и скоро свяжется с вами.</p>

        @include('orders._result')
    </div>
</div>

<style>
.order-result{max-width:680px;margin:40px auto;padding:28px;border:1px solid #e8e8e8;border-radius:14px;background:#fff;box-shadow:0 10px 30px rgba(40,30,60,.06)}
.order-result h1{margin:0 0 6px;font-size:30px}.order-result__lead{margin:0 0 20px;color:#666;font-size:16px}
@media(max-width:575px){.order-result{margin:20px 0;padding:20px 16px}.order-result h1{font-size:25px}}
@media print{header,footer,.order-result button,.order-result a{display:none!important}.order-result{border:0;margin:0;max-width:none;box-shadow:none}}
</style>
@endsection
