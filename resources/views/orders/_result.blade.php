{{-- Содержимое страницы «Заказ принят»: что заказано, кто продавец, что будет дальше. Общее для всех тем. --}}
@php
    $orTotal = (float) $order->unit_price * $order->quantity;
    $orSeller = $order->seller;
    $orSellerName = trim((string) ($order->seller_name ?: $orSeller?->name), '" ');
    $orSellerPhone = trim((string) $orSeller?->phone);
    $orSellerPhoneHref = preg_replace('/[^\d+]/', '', $orSellerPhone);
    $orMoney = static fn (float $value): string => number_format($value, 0, '.', ' ').' ₽';
@endphp

<div class="order-result-box">
    <dl class="order-result-box__rows">
        <div><dt>Номер заказа</dt><dd><strong>{{ $order->number }}</strong></dd></div>
        <div><dt>Товар</dt><dd>{{ $order->product_name }}</dd></div>
        <div><dt>Количество</dt><dd>{{ $order->quantity }}</dd></div>
        <div><dt>Сумма</dt><dd><strong>{{ $orMoney($orTotal) }}</strong></dd></div>
        <div>
            <dt>Получение</dt>
            <dd>
                @if($order->isDelivery())
                    Доставка продавцом@if(filled($order->delivery_address)): {{ $order->delivery_address }}@endif
                @else
                    Самовывоз у продавца
                @endif
            </dd>
        </div>
        @if($orSellerName !== '')
            <div><dt>Продавец</dt><dd>{{ $orSellerName }}</dd></div>
        @endif
    </dl>

    <h2 class="order-result-box__title">Что будет дальше</h2>
    <ol class="order-result-box__steps">
        <li><b>1</b><span>Продавец позвонит вам по номеру <strong>{{ $order->customer_phone }}</strong>.</span></li>
        <li><b>2</b><span>Подтвердит наличие и цену{{ $order->isDelivery() ? ' и договорится о доставке' : ' и скажет, когда можно забрать товар' }}.</span></li>
        <li><b>3</b><span>Вы получаете товар и платите продавцу напрямую: сайт оплату не принимает.</span></li>
    </ol>

    <div class="order-result-box__actions">
        @if($orSellerPhone !== '')
            <a class="order-result-box__btn order-result-box__btn--primary" href="tel:{{ $orSellerPhoneHref }}">Позвонить продавцу: {{ $orSellerPhone }}</a>
        @endif
        <a class="order-result-box__btn" href="{{ route('orders.index') }}">Мои заказы</a>
        <a class="order-result-box__btn" href="{{ route('catalog.index') }}">Продолжить покупки</a>
        <button type="button" class="order-result-box__btn" onclick="window.print()">Распечатать</button>
    </div>
</div>

<style>
.order-result-box{--ob-grad:linear-gradient(90deg,#b965ed,#4ed9ee);--ob-accent:#8750b4;--ob-ink:#222;--ob-muted:#767676;--ob-line:#e4e4ea;--ob-soft:#f6f3fb}
.theme-new .order-result-box{--ob-grad:var(--bimm-gold-dark,#151515);--ob-accent:var(--bimm-gold,#555dff);--ob-line:var(--bimm-line,#e6e6e6);--ob-soft:#f4f5ff}
.theme-joom .order-result-box{--ob-grad:var(--joom-red,#555dff);--ob-accent:var(--joom-red,#555dff);--ob-line:var(--joom-line,#e6e8eb);--ob-soft:var(--joom-primary-soft,#f0efff)}
.order-result-box__rows{margin:0 0 22px;padding:4px 0;border-top:1px solid var(--ob-line);border-bottom:1px solid var(--ob-line)}
.order-result-box__rows>div{display:grid;grid-template-columns:140px minmax(0,1fr);gap:12px;padding:9px 0;border-bottom:1px solid var(--ob-line)}
.order-result-box__rows>div:last-child{border-bottom:0}
.order-result-box__rows dt{margin:0;font-weight:400;color:var(--ob-muted)}.order-result-box__rows dd{margin:0;overflow-wrap:anywhere}
.order-result-box__title{margin:0 0 12px;font-size:18px;font-weight:700}
.order-result-box__steps{display:grid;gap:12px;margin:0 0 24px;padding:0;list-style:none}
.order-result-box__steps li{display:flex;align-items:flex-start;gap:12px;line-height:1.45}
.order-result-box__steps b{display:inline-flex;align-items:center;justify-content:center;flex:0 0 26px;width:26px;height:26px;border-radius:50%;background:var(--ob-soft);color:var(--ob-accent);font-size:13px}
.order-result-box__actions{display:flex;flex-wrap:wrap;gap:10px}
.order-result-box__btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 18px;border:1px solid var(--ob-line);border-radius:10px;background:#fff;color:var(--ob-ink);font-size:15px;font-weight:600;cursor:pointer;text-decoration:none}
.order-result-box__btn:hover,.order-result-box__btn:focus{border-color:var(--ob-accent);color:var(--ob-accent);text-decoration:none}
.order-result-box__btn--primary{border-color:transparent;background:var(--ob-grad);color:#fff}
.order-result-box__btn--primary:hover,.order-result-box__btn--primary:focus{color:#fff;opacity:.92}
@media(max-width:575px){.order-result-box__rows>div{grid-template-columns:1fr;gap:2px}.order-result-box__btn{width:100%}}
@media print{.order-result-box__actions{display:none!important}}
</style>
