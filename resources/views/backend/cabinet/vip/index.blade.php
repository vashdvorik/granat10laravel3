{{-- VIP-размещение: все товары магазина, статус VIP, покупка и история --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
@php
    use App\Support\Money;
    use App\Support\Plural;
@endphp
<div class="contents vip-page">
    <div class="container-fluid">
        @include('backend.cabinet.partials.flash')

        <div class="row">
            <div class="col-12">
                <div class="breadcrumb-main">
                    <h4 class="breadcrumb-title">VIP-размещение</h4>
                </div>
            </div>
        </div>

        {{-- Сводка --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card h-100"><div class="card-body">
                    <small class="text-muted d-block mb-1">Баланс</small>
                    <strong class="fs-20 d-block">{{ Money::rubles($balanceCents) }}</strong>
                    <a href="{{ route('account.user.balance.topup') }}" class="fs-13">Пополнить</a>
                </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100"><div class="card-body">
                    <small class="text-muted d-block mb-1">Цена VIP</small>
                    <strong class="fs-20 d-block">{{ Money::rubles($pricePerDayCents) }}</strong>
                    <span class="text-muted fs-13">за день на один товар</span>
                </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100"><div class="card-body">
                    <small class="text-muted d-block mb-1">Сейчас в VIP</small>
                    <strong class="fs-20 d-block">{{ $activeCount }}</strong>
                    <span class="text-muted fs-13">{{ Plural::ru($activeCount, 'товар', 'товара', 'товаров') }}</span>
                </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 {{ $expiringCount > 0 ? 'border-warning' : '' }}"><div class="card-body">
                    <small class="text-muted d-block mb-1">Скоро закончится</small>
                    <strong class="fs-20 d-block">{{ $expiringCount }}</strong>
                    <span class="text-muted fs-13">в ближайшие 3 дня</span>
                </div></div>
            </div>
        </div>

        {{-- Что даёт VIP и как это работает --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <h6 class="mb-2"><i class="uil uil-star text-warning"></i> Что даёт VIP</h6>
                        <ul class="mb-0 ps-3 text-muted fs-14">
                            <li>Товар показывается выше остальных в каталоге, поиске и на главной.</li>
                            <li>На карточке появляется значок «VIP», покупатели замечают её первой.</li>
                            <li>Оплата с баланса за нужное число дней, без подписок и автосписаний.</li>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <h6 class="mb-2">Как это работает</h6>
                        <ol class="mb-0 ps-3 text-muted fs-14">
                            <li><strong>Включить:</strong> выберите срок у товара ниже и нажмите «Включить».</li>
                            <li><strong>Продлить:</strong> новый срок добавляется к текущему.</li>
                            <li><strong>Отключить:</strong> в любой момент, вернём деньги за неиспользованные полные дни.</li>
                            <li><strong>Не хватает денег:</strong> пополните баланс картой, сумма подставится сама.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Товары --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="fw-500 mb-0">Ваши товары</h6></div>
            <div class="card-body p-0">
                @if($rows->isEmpty())
                    <div class="text-center py-5 px-3">
                        <i class="uil uil-box fs-48 text-muted d-block mb-2"></i>
                        <h6 class="mb-1">Товаров пока нет</h6>
                        <p class="text-muted mb-3">Добавьте товар, после проверки его можно будет поднять в VIP.</p>
                        <a href="{{ route('account.store.products.create') }}" class="btn btn-primary btn-squared">Добавить товар</a>
                    </div>
                @else
                    <div class="vip-list">
                        @foreach($rows as $row)
                            @php
                                $product = $row['product'];
                                $state = $row['state'];
                                $image = $product->images->firstWhere('is_main', true) ?? $product->images->first();
                            @endphp
                            <div class="vip-row" id="vip-product-{{ $product->id }}">
                                <div class="vip-row__product">
                                    @if($image)
                                        <img src="{{ asset('storage/'.$image->path) }}" alt="" class="vip-row__thumb">
                                    @else
                                        <span class="vip-row__thumb vip-row__thumb--empty"><i class="uil uil-image-slash"></i></span>
                                    @endif
                                    <div class="vip-row__title">
                                        <a href="{{ route('account.store.products.show', $product) }}#vip" class="fw-bold">{{ $product->name }}</a>
                                        <div class="vip-row__status">
                                            @if($state->isPermanent())
                                                <span class="badge badge-round badge-warning">VIP без срока</span>
                                            @elseif($state->isActive())
                                                <span class="badge badge-round badge-warning">VIP до {{ $state->until->format('d.m.Y') }}</span>
                                                <span class="text-muted fs-13">
                                                    осталось {{ Plural::days((int) $state->daysLeft) }}{{ $state->expiresSoon ? ' — пора продлить' : '' }}
                                                </span>
                                            @else
                                                <span class="badge badge-round badge-light">Не VIP</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="vip-row__action">
                                    @if($state->isPermanent())
                                        <span class="text-muted fs-13">Выдан администратором, продлевать не нужно.</span>
                                    @elseif(! $state->eligible)
                                        <span class="text-muted fs-13 d-block">{{ $state->blockers[0]['message'] ?? 'VIP пока недоступен.' }}</span>
                                        <a href="{{ route('account.store.products.show', $product) }}" class="fs-13">Открыть товар</a>
                                    @else
                                        <form action="{{ route('account.store.products.vip', $product) }}" method="POST"
                                              data-vip-form="buy" data-extend="{{ $state->isActive() ? 1 : 0 }}" data-product="{{ $product->name }}"
                                              class="vip-row__form">
                                            @csrf
                                            <select name="days" class="form-select form-select-sm" aria-label="Срок VIP для {{ $product->name }}">
                                                @foreach($state->options as $option)
                                                    <option value="{{ $option['days'] }}"
                                                            data-price="{{ Money::rubles($option['price_cents']) }}"
                                                            data-until="{{ $option['new_until']->format('d.m.Y H:i') }}"
                                                            @disabled(! $option['affordable'])
                                                            @selected($loop->first)>
                                                        {{ $option['label'] }} — {{ Money::rubles($option['price_cents']) }}{{ $option['affordable'] ? '' : ' (не хватает денег)' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-warning btn-sm btn-squared" @disabled($state->options === [] || collect($state->options)->every(fn ($o) => ! $o['affordable']))>
                                                {{ $state->isActive() ? 'Продлить' : 'Включить' }}
                                            </button>
                                        </form>

                                        @if(collect($state->options)->every(fn ($o) => ! $o['affordable']))
                                            <a href="{{ route('account.user.balance.topup', ['amount' => (int) ceil($state->cheapestShortfallCents() / 100)]) }}" class="fs-13 d-block mt-1">
                                                Пополнить баланс на {{ Money::rubles((int) ceil($state->cheapestShortfallCents() / 100) * 100) }}
                                            </a>
                                        @endif

                                        @foreach($state->warnings as $warning)
                                            <small class="text-muted d-block mt-1">{{ $warning }}</small>
                                        @endforeach

                                        @if($state->isActive() && $state->canCancel)
                                            <form action="{{ route('account.store.products.vip.cancel', $product) }}" method="POST"
                                                  data-vip-form="cancel"
                                                  data-confirm-text="Вернём на баланс {{ Money::rubles($state->refundCents) }} за неиспользованные полные дни. VIP сразу перестанет работать."
                                                  class="mt-1">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link btn-sm text-danger p-0">
                                                    Отключить{{ $state->refundCents > 0 ? ' (вернём '.Money::rubles($state->refundCents).')' : '' }}
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($products->hasPages())
                        <div class="p-3">{{ $products->links() }}</div>
                    @endif
                @endif
            </div>
        </div>

        {{-- История покупок --}}
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h6 class="fw-500 mb-0">История VIP</h6>
                <a href="{{ route('account.balance.index', ['type' => 'vip_purchase']) }}" class="fs-13">Все списания на VIP</a>
            </div>
            <div class="card-body">
                @if($history->isEmpty())
                    <p class="text-muted mb-0">Покупок VIP пока не было.</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Куплено</th>
                                    <th>Товар</th>
                                    <th>Период</th>
                                    <th class="text-end">Стоимость</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($history as $purchase)
                                    <tr>
                                        <td style="white-space: nowrap;">{{ $purchase->created_at->format('d.m.Y H:i') }}</td>
                                        <td>{{ $purchase->product?->name ?? 'Товар удалён' }}</td>
                                        <td style="white-space: nowrap;">
                                            {{ Plural::days($purchase->days) }}<small class="text-muted d-block">до {{ $purchase->ends_at->format('d.m.Y') }}</small>
                                        </td>
                                        <td class="text-end" style="white-space: nowrap;">
                                            {{ Money::rubles($purchase->price_cents) }}
                                            @if($purchase->refunded_cents > 0)
                                                <small class="text-success d-block">возврат {{ Money::rubles($purchase->refunded_cents) }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $purchase->statusLabel() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@include('backend.cabinet.vip.partials.confirm-script')
@endsection
