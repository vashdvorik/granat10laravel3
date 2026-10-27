@extends('main_layots')

@section('title', 'Мои заказы — BIMM')

@section('meta')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<main class="container container-240 customer-orders-page">
    <ul class="breadcrumb v4">
        <li><a href="{{ $regionalUrl->route('home') }}">Главная</a></li>
        <li class="active">Мои заказы</li>
    </ul>

    <section class="customer-orders-hero" aria-labelledby="customer-orders-title">
        <div>
            
            <h1 id="customer-orders-title">Мои заказы</h1>
            <p>Здесь сохраняются заказы, оформленные с этого устройства.</p>
        </div>

        @if($orders->isNotEmpty())
            <form method="POST" action="{{ route('orders.clear') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="market-btn market-btn--secondary">Очистить список</button>
            </form>
        @endif
    </section>

    @if(session('status'))
        <div class="customer-orders-alert" role="status">{{ session('status') }}</div>
    @endif

    @if($orders->isEmpty())
        <section class="customer-orders-empty">
            <div class="customer-orders-empty__icon" aria-hidden="true">
                <i class="fa fa-clipboard-list"></i>
            </div>
            <h2>У вас пока нет сохранённых заказов</h2>
            <p>Когда вы оформите заказ на BIMM, он появится здесь и останется доступен в этом браузере.</p>
            <a href="{{ $regionalUrl->route('catalog.index') }}" class="home-btn home-btn--primary">Перейти в каталог</a>
        </section>
    @else
        <section class="customer-orders-list" aria-label="Список заказов">
            @foreach($orders as $order)
                <article class="customer-order-card">
                    <div class="customer-order-card__main">
                        <div class="customer-order-card__top">
                            <strong>{{ $order->number }}</strong>
                            <span>{{ $order->status->label() }}</span>
                        </div>

                        <h2>{{ $order->product_name }}</h2>

                        <dl class="customer-order-card__meta">
                            <div>
                                <dt>Дата</dt>
                                <dd>{{ $order->created_at->format('d.m.Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt>Количество</dt>
                                <dd>{{ $order->quantity }}</dd>
                            </div>
                            <div>
                                <dt>Сумма</dt>
                                <dd>{{ number_format((float) $order->unit_price * $order->quantity, 2, ',', ' ') }} руб.</dd>
                            </div>
                            <div>
                                <dt>Продавец</dt>
                                <dd>{{ trim($order->seller_name, '" ') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="customer-order-card__actions">
                        <a href="{{ route('orders.show', $order->public_id) }}" class="market-btn market-btn--primary">Открыть заказ</a>
                        <form method="POST" action="{{ route('orders.destroy', $order->public_id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="market-btn market-btn--secondary">Удалить из списка</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </section>
    @endif
</main>

@push('styles')
<style>
    .customer-orders-page{padding-top:28px;padding-bottom:80px;color:var(--market-text,#222228)}
    .customer-orders-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin:10px 0 26px}
    .customer-orders-hero h1{margin:0;font-size:38px;font-weight:700;line-height:1.15}
    .customer-orders-hero p{max-width:620px;margin:12px 0 0;color:var(--market-muted,#747785);font-size:16px;line-height:1.6}
    .customer-orders-alert{margin-bottom:18px;padding:13px 16px;border:1px solid #d9d4df;border-radius:12px;background:#fbf7ff;color:#6f3a9c}
    .customer-orders-list{display:grid;gap:14px}
    .customer-order-card{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:24px;align-items:center;padding:20px;border:1px solid var(--market-border,#e8e8ed);border-radius:16px;background:#fff}
    .customer-order-card__top{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:8px}
    .customer-order-card__top strong{font-size:15px}
    .customer-order-card__top span{padding:5px 9px;border-radius:999px;background:#f1e8fb;color:#7c42ae;font-size:12px;font-weight:700}
    .customer-order-card h2{margin:0 0 14px;font-size:20px;font-weight:700;line-height:1.35}
    .customer-order-card__meta{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:0}
    .customer-order-card__meta dt{color:var(--market-muted,#747785);font-size:12px;font-weight:500}
    .customer-order-card__meta dd{margin:4px 0 0;font-weight:700}
    .customer-order-card__actions{display:grid;gap:10px;min-width:170px}
    .customer-order-card__actions .market-btn{width:100%}
    /* Пустой список: та же компактная строка, что у пустого «Избранного» (значок, заголовок с подсказкой, кнопка справа) */
    .customer-orders-empty{display:grid;grid-template-columns:52px minmax(0,1fr) auto;column-gap:18px;row-gap:2px;align-items:center;padding:18px 24px;border:1px solid #ebebef;border-radius:16px;background:#fff}
    .customer-orders-empty__icon{display:flex;grid-row:1 / span 2;align-items:center;justify-content:center;width:52px;height:52px;border-radius:14px;background:#f1e8fb;color:#9152c7;font-size:22px}
    .customer-orders-empty h2{grid-column:2;margin:0;font-size:20px;line-height:1.25}
    .customer-orders-empty p{grid-column:2;max-width:620px;margin:0;color:var(--market-muted,#747785);font-size:14px;line-height:1.5}
    .customer-orders-empty .home-btn{grid-column:3;grid-row:1 / span 2;min-height:42px;padding:0 16px;font-size:14px;white-space:nowrap}
    @media(max-width:767px){
        .customer-orders-page{padding-top:18px;padding-bottom:96px}
        .customer-orders-hero{display:block}
        .customer-orders-hero h1{font-size:28px}
        .customer-orders-hero form{margin-top:16px}
        .customer-orders-hero .market-btn{width:100%}
        .customer-order-card{grid-template-columns:1fr;gap:16px;padding:16px}
        .customer-order-card__meta{grid-template-columns:repeat(2,minmax(0,1fr))}
        .customer-order-card__actions{min-width:0}
        .customer-orders-empty{grid-template-columns:44px minmax(0,1fr);column-gap:12px;padding:14px}
        .customer-orders-empty__icon{width:44px;height:44px;border-radius:12px;font-size:19px}
        .customer-orders-empty h2{font-size:18px}
        .customer-orders-empty p{font-size:13.5px}
        .customer-orders-empty .home-btn{grid-column:1 / -1;grid-row:auto;margin-top:12px}
    }
</style>
@endpush
@endsection
