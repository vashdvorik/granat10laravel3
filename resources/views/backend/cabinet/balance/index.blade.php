{{-- Единый журнал баланса: все операции продавца --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
@php
    use App\Support\Money;
    $filtered = $selectedType !== null || $selectedPeriod !== 'all';
@endphp
<div class="contents">
    <div class="container-fluid">
        @include('backend.cabinet.partials.flash')

        <div class="row">
            <div class="col-12">
                <div class="breadcrumb-main">
                    <h4 class="breadcrumb-title">Баланс и операции</h4>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="cabinet-balance-card p-4 mb-3">
                            <p class="mb-2 text-muted">Доступно средств</p>
                            <div class="cabinet-balance-card__amount">{{ Money::rubles($balanceCents) }}</div>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="{{ route('account.user.balance.topup') }}" class="btn btn-success btn-squared">
                                <i class="uil uil-plus-circle"></i> Пополнить баланс
                            </a>
                            <a href="{{ route('account.vip.index') }}" class="btn btn-outline-secondary btn-squared">
                                <i class="uil uil-star"></i> Потратить на VIP-размещение
                            </a>
                        </div>
                        <small class="text-muted d-block mt-3">
                            Баланс можно потратить на VIP-размещение товаров. Все списания и пополнения записываются ниже.
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="row g-3 h-100">
                    <div class="col-sm-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <small class="text-muted d-block mb-1">Поступило{{ $filtered ? ' (по фильтру)' : '' }}</small>
                                <strong class="fs-24 text-success d-block">+{{ Money::rubles($credited) }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <small class="text-muted d-block mb-1">Потрачено{{ $filtered ? ' (по фильтру)' : '' }}</small>
                                <strong class="fs-24 text-danger d-block">−{{ Money::rubles($spent) }}</strong>
                            </div>
                        </div>
                    </div>
                    @if($pendingTopups->isNotEmpty())
                        <div class="col-12">
                            <div class="alert alert-warning mb-0">
                                <strong>Есть неоплаченные счета.</strong>
                                @foreach($pendingTopups as $pending)
                                    <div>Счёт №{{ $pending->invoiceId() }} на {{ Money::rubles($pending->amount_cents) }} — оплатить можно до {{ $pending->expires_at->format('d.m H:i') }}.</div>
                                @endforeach
                                <small class="d-block mt-1">Деньги на баланс зачисляются только после подтверждения оплаты банком.</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h6 class="fw-500 mb-0">История операций</h6>
                @if($transactions->total() > 0)
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-secondary btn-squared">
                        <i class="uil uil-download-alt"></i> Скачать CSV
                    </a>
                @endif
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('account.balance.index') }}" class="row g-2 align-items-end mb-4">
                    <div class="col-md-5">
                        <label for="journal-type" class="form-label mb-1 fs-13 text-muted">Тип операции</label>
                        <select id="journal-type" name="type" class="form-select">
                            <option value="">Все операции</option>
                            @foreach($types as $value => $label)
                                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="journal-period" class="form-label mb-1 fs-13 text-muted">Период</label>
                        <select id="journal-period" name="period" class="form-select">
                            @foreach($periods as $value => $label)
                                <option value="{{ $value }}" @selected($selectedPeriod === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-squared flex-fill">Показать</button>
                        @if($filtered)
                            <a href="{{ route('account.balance.index') }}" class="btn btn-outline-secondary btn-squared">Сбросить</a>
                        @endif
                    </div>
                </form>

                @if($transactions->isEmpty())
                    <div class="text-center py-5">
                        <i class="uil uil-receipt fs-48 text-muted d-block mb-2"></i>
                        <h6 class="mb-1">{{ $filtered ? 'По выбранным условиям операций нет' : 'Операций пока нет' }}</h6>
                        <p class="text-muted mb-3">
                            {{ $filtered ? 'Попробуйте изменить фильтр.' : 'Здесь появятся пополнения баланса, покупки VIP и возвраты.' }}
                        </p>
                        @unless($filtered)
                            <a href="{{ route('account.user.balance.topup') }}" class="btn btn-success btn-squared">Пополнить баланс</a>
                        @endunless
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="white-space: nowrap;">Дата</th>
                                    <th>Операция</th>
                                    <th class="text-end" style="white-space: nowrap;">Сумма</th>
                                    <th class="text-end" style="white-space: nowrap;">Баланс после</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $transaction)
                                    <tr>
                                        <td style="white-space: nowrap;">
                                            {{ $transaction->created_at->format('d.m.Y') }}
                                            <small class="text-muted d-block">{{ $transaction->created_at->format('H:i') }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="uil {{ $transaction->type->icon() }} fs-20 text-muted mt-1"></i>
                                                <div>
                                                    <strong class="d-block">{{ $transaction->type->label() }}</strong>
                                                    <small class="text-muted">{{ $transaction->description }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end fw-bold {{ $transaction->isCredit() ? 'text-success' : 'text-danger' }}" style="white-space: nowrap;">
                                            {{ $transaction->amountFormatted() }} ₽
                                        </td>
                                        <td class="text-end text-muted" style="white-space: nowrap;">{{ $transaction->balanceAfterFormatted() }} ₽</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $transactions->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
