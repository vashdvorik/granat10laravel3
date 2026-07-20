@extends('main_layots')

@section('title', 'Помощь — BIMM')

@section('content')
<header class="bimm-page-head"><span class="bimm-kicker">Поддержка</span><h1>Как работает BIMM</h1><p>Всё необходимое, чтобы быстро найти товар, связаться с магазином и оформить заказ.</p></header>
<div class="bimm-how"><article><span>01</span><h3>Найдите товар</h3><p>Введите запрос в поиске или выберите категорию. Используйте региональный фильтр, чтобы видеть предложения рядом.</p></article><article><span>02</span><h3>Изучите карточку</h3><p>В карточке доступны цена, описание, характеристики, адрес и контакты продавца.</p></article><article><span>03</span><h3>Оформите покупку</h3><p>Если магазин принимает заказы через BIMM, заполните форму. В остальных случаях свяжитесь напрямую.</p></article></div>
<section class="bimm-section"><div class="bimm-section__head"><div><span class="bimm-kicker">Частые вопросы</span><h2>Ответы рядом</h2></div></div><div style="display:grid;gap:10px">@foreach(['Как связаться с продавцом?' => 'Откройте карточку товара или страницу магазина и используйте телефон либо доступную социальную сеть.', 'Как работает оплата?' => 'BIMM не принимает оплату за продавца. Способ и момент оплаты согласуются напрямую.', 'Как добавить товар в избранное?' => 'Нажмите на сердечко в карточке товара. Список хранится в вашем браузере.', 'Как стать продавцом?' => 'Создайте аккаунт, войдите в кабинет и оформите страницу магазина.'] as $question => $answer)<details style="padding:18px 20px;border:1px solid var(--bimm-line);border-radius:15px;background:#fff"><summary style="cursor:pointer;font-weight:800">{{ $question }}</summary><p style="margin:12px 0 0;color:var(--bimm-muted);line-height:1.6">{{ $answer }}</p></details>@endforeach</div></section>
@endsection
