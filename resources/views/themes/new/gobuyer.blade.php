@extends('main_layots')

@section('title', 'Продавцам — BIMM')

@section('content')
<section class="bimm-hero"><div class="bimm-hero__content"><span class="bimm-kicker">Для бизнеса</span><h1>Покажите свой магазин тем, кто рядом.</h1><p>Создайте витрину, публикуйте товары и принимайте обращения от покупателей в одном месте.</p><a class="bimm-button bimm-button--gold" href="{{ Route::has('register') ? route('register') : route('account.index') }}">Начать продавать</a></div></section>
<section class="bimm-section"><div class="bimm-section__head"><div><span class="bimm-kicker">Преимущества</span><h2>Всё для локального продавца</h2></div></div><div class="bimm-how"><article><span>01</span><h3>Ваша витрина</h3><p>Расскажите о магазине, добавьте логотип, адрес, контакты и ссылки.</p></article><article><span>02</span><h3>Управление товарами</h3><p>Добавляйте фотографии, цены, характеристики и управляйте публикациями из кабинета.</p></article><article><span>03</span><h3>Больше доверия</h3><p>Покупатель видит реальные условия и связывается с вами напрямую.</p></article></div></section>
@endsection
