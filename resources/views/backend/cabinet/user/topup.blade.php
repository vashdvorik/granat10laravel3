{{-- Пополнение баланса банковской картой --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
<div class="contents">
    <div class="container-fluid">
        @include('backend.cabinet.partials.flash')
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-main">
                    <h4 class="breadcrumb-title">Пополнение баланса</h4>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="fw-500 mb-0">Оплата банковской картой</h6>
                    </div>
                    <div class="card-body">
                        <div class="cabinet-balance-card p-4 mb-4">
                            <p class="mb-2 text-muted">Доступно средств</p>
                            <div class="cabinet-balance-card__amount">
                                {{ number_format($user->balance ?? 0, 2, '.', ' ') }} ₽
                            </div>
                        </div>

                        @if($available)
                            <form method="POST" action="{{ route('account.user.balance.topup.store') }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="topup-amount" class="form-label">Сумма пополнения, ₽</label>
                                    <input type="text" inputmode="decimal" id="topup-amount" name="amount"
                                           value="{{ old('amount') }}" autocomplete="off"
                                           class="form-control @error('amount') is-invalid @enderror"
                                           placeholder="Например, 500">
                                    @error('amount')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">От {{ $minAmount }} до {{ $maxAmount }} ₽</small>
                                </div>
                                <button type="submit" class="btn btn-success btn-squared w-100">
                                    <i class="uil uil-credit-card"></i> Перейти к оплате
                                </button>
                                <small class="text-muted d-block mt-3">
                                    Оплата проходит на защищённой странице банка. Данные карты на нашем сайте не вводятся и не хранятся.
                                </small>
                            </form>
                        @else
                            <div class="alert alert-warning mb-0">
                                Пополнение картой сейчас недоступно. Если нужно пополнить баланс, свяжитесь с технической поддержкой — контакты внизу страницы.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-7" id="history">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="fw-500 mb-0">История пополнений</h6>
                    </div>
                    <div class="card-body">
                        @if($history->isEmpty())
                            <p class="text-muted mb-0">Пополнений пока не было.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Дата</th>
                                            <th>Сумма</th>
                                            <th>Статус</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($history as $topup)
                                            <tr>
                                                <td>{{ $topup->created_at->format('d.m.Y H:i') }}</td>
                                                <td>{{ number_format($topup->amount_cents / 100, 2, '.', ' ') }} ₽</td>
                                                <td>{{ $topup->statusLabel() }}{{ $topup->is_test ? ' (тест)' : '' }}</td>
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
    </div>
</div>
@endsection
