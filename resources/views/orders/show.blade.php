@extends('main_layots')

@section('title', 'Заказ принят — BIMM')

@section('meta')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<main class="container container-240 order-result-page">
    <h1 class="order-result-title">Заказ принят</h1>
    <p class="order-result__lead">Продавец получил вашу заявку и скоро свяжется с вами.</p>

    <div class="order-result">
        @include('orders._result')
    </div>
</main>

<style>
/* Как страница оформления: в сетке сайта (боковые отступы шапки), заголовок над карточкой, карточка без тени с тонкой рамкой */
.order-result-page{padding-top:22px;padding-bottom:70px;color:#222}
.order-result-title{margin:0 0 8px;font-size:32px;line-height:1.15;font-weight:700}
.order-result__lead{margin:0 0 22px;color:#666;font-size:16px}
.order-result{max-width:774px;padding:22px;border:1px solid #e4e4ea;border-radius:14px;background:#fff}
@media(max-width:767px){.order-result-page{padding-bottom:110px}}
@media(max-width:575px){.order-result{padding:18px 16px}.order-result-title{font-size:25px}}
@media print{header,footer,.order-result button,.order-result a{display:none!important}.order-result{border:0;max-width:none;padding:0}}
</style>
@endsection
