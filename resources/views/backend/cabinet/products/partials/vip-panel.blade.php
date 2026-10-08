{{-- VIP-размещение товара: одно место, где продавцу всё понятно — что даёт VIP, как включить, продлить, отключить и оплатить. --}}
@php
    use App\Support\Money;

    /** @var \App\Data\VipState $vipState */
    $s = $vipState;
    $firstAffordable = collect($s->options)->first(fn ($o) => $o['affordable']);
    $defaultDays = ($firstAffordable ?? ($s->options[0] ?? null))['days'] ?? null;
@endphp

<div class="card mb-3 product-vip-panel" id="vip">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <h6 class="mb-0"><i class="uil uil-star text-warning"></i> VIP-размещение</h6>

            @if($s->isPermanent())
                <span class="badge badge-round badge-warning badge-lg">VIP без срока</span>
            @elseif($s->isActive())
                <span class="badge badge-round badge-warning badge-lg">
                    VIP до {{ $s->until->format('d.m.Y') }}
                </span>
            @else
                <span class="badge badge-round badge-light badge-lg">VIP выключен</span>
            @endif
        </div>

        <p class="text-muted fs-14 mb-3">
            VIP поднимает товар выше остальных в каталоге, поиске и на главной. Покупатели пометки «VIP» не видят.
            Оплата с баланса, действует сразу после оплаты.
        </p>

        {{-- 1. VIP без срока, выдан администратором --}}
        @if($s->isPermanent())
            <div class="alert alert-success py-2 px-3 mb-0">
                VIP выдан администратором без ограничения по сроку. Покупать ничего не нужно.
            </div>

        {{-- 2. Купить пока нельзя: объясняем почему и что сделать --}}
        @elseif(! $s->eligible)
            <div class="alert alert-warning py-2 px-3 mb-2">
                <strong>VIP пока недоступен.</strong>
                <ul class="mb-0 ps-3 mt-1">
                    @foreach($s->blockers as $blocker)
                        <li>{{ $blocker['message'] }}</li>
                    @endforeach
                </ul>
            </div>
            @if($s->isActive())
                <div class="alert alert-info py-2 px-3 mb-2">
                    VIP уже оплачен до <strong>{{ $s->until->format('d.m.Y H:i') }}</strong>, срок продолжает идти.
                </div>
            @endif
            <small class="text-muted d-block">Стоимость: {{ Money::rubles($s->pricePerDayCents) }} за день.</small>

        {{-- 3. Можно включить или продлить --}}
        @else
            @if($s->isActive())
                <div class="alert {{ $s->expiresSoon ? 'alert-warning' : 'alert-success' }} py-2 px-3 mb-3">
                    VIP действует до <strong>{{ $s->until->format('d.m.Y H:i') }}</strong>
                    (осталось {{ \App\Support\Plural::days((int) $s->daysLeft) }}).
                    @if($s->expiresSoon)
                        <br>Скоро закончится: продлите заранее. Новый срок добавится к текущему, ничего не потеряется.
                    @else
                        <br>Продлить можно в любой момент: новый срок добавится к текущему.
                    @endif
                </div>
            @endif

            @foreach($s->warnings as $warning)
                <div class="alert alert-light border py-2 px-3 mb-3 fs-13">{{ $warning }}</div>
            @endforeach

            <form action="{{ route('account.store.products.vip', $product) }}" method="POST"
                  data-vip-form="buy" data-extend="{{ $s->isActive() ? 1 : 0 }}" data-product="{{ $product->name }}"
                  class="product-vip-form" id="vip-buy-form-{{ $product->id }}">
                @csrf

                <div class="vip-options" role="radiogroup" aria-label="Срок VIP">
                    @foreach($s->options as $option)
                        <label class="vip-option">
                            <input type="radio" name="days" value="{{ $option['days'] }}"
                                   data-price="{{ Money::rubles($option['price_cents']) }}"
                                   data-price-cents="{{ $option['price_cents'] }}"
                                   data-shortfall-cents="{{ $option['shortfall_cents'] }}"
                                   data-shortfall="{{ Money::rubles($option['shortfall_cents']) }}"
                                   data-balance-after="{{ Money::rubles($s->balanceCents - $option['price_cents']) }}"
                                   data-until="{{ $option['new_until']->format('d.m.Y H:i') }}"
                                   @checked((int) old('days', $defaultDays) === $option['days'])>
                            <span class="vip-option__body">
                                <span class="vip-option__title">{{ $option['label'] }}</span>
                                <span class="vip-option__price">{{ Money::rubles($option['price_cents']) }}</span>
                                <span class="vip-option__meta">
                                    {{ Money::rubles($option['per_day_cents']) }} в день<br>
                                    {{ $s->isActive() ? 'продлит до' : 'до' }} {{ $option['new_until']->format('d.m.Y') }}
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('days')
                    <div class="text-danger fs-13 mb-2">{{ $message }}</div>
                @enderror

                <div class="vip-summary mb-3" data-vip-summary>
                    <div>На балансе: <strong>{{ Money::rubles($s->balanceCents) }}</strong></div>
                    <div data-vip-after></div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="submit" class="btn btn-warning btn-default btn-squared" data-vip-submit>
                        {{ $s->isActive() ? 'Продлить VIP' : 'Включить VIP' }}
                    </button>
                    <a href="{{ route('account.user.balance.topup', ['amount' => $s->cheapestShortfallCents() > 0 ? (int) ceil($s->cheapestShortfallCents() / 100) : null]) }}"
                       class="btn btn-outline-secondary btn-default btn-squared d-none" data-vip-topup>
                        Пополнить баланс
                    </a>
                </div>
            </form>

            <script>
            (function () {
                var form = document.getElementById('vip-buy-form-{{ $product->id }}');
                if (!form) { return; }
                var after = form.querySelector('[data-vip-after]');
                var submit = form.querySelector('[data-vip-submit]');
                var topup = form.querySelector('[data-vip-topup]');
                var topupBase = @json(route('account.user.balance.topup'));

                function update() {
                    var option = form.querySelector('input[name="days"]:checked');
                    if (!option) { return; }
                    var short = parseInt(option.dataset.shortfallCents, 10) || 0;

                    if (short > 0) {
                        after.innerHTML = '<span class="text-danger">Не хватает <strong>' + option.dataset.shortfall + '</strong>. Пополните баланс и вернитесь сюда.</span>';
                        submit.disabled = true;
                        topup.classList.remove('d-none');
                        topup.href = topupBase + '?amount=' + Math.ceil(short / 100);
                    } else {
                        after.innerHTML = 'Спишется <strong>' + option.dataset.price + '</strong>, останется <strong>' + option.dataset.balanceAfter + '</strong>';
                        submit.disabled = false;
                        topup.classList.add('d-none');
                    }
                }

                form.addEventListener('change', update);
                update();
            })();
            </script>
        @endif

        {{-- 4. Досрочное отключение --}}
        @if($s->isActive() && ! $s->isPermanent())
            <hr class="my-3">
            @if($s->canCancel)
                <form action="{{ route('account.store.products.vip.cancel', $product) }}" method="POST"
                      data-vip-form="cancel"
                      data-confirm-text="Вернём на баланс {{ Money::rubles($s->refundCents) }} за неиспользованные полные дни. VIP сразу перестанет работать."
                      class="d-flex flex-wrap align-items-center gap-2">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm btn-squared">Отключить VIP досрочно</button>
                    <small class="text-muted">
                        @if($s->refundCents > 0)
                            Вернём {{ Money::rubles($s->refundCents) }} за неиспользованные полные дни.
                        @else
                            Возврат не положен: неиспользованных полных дней нет.
                        @endif
                    </small>
                </form>
            @elseif($s->cancelBlockedReason)
                <small class="text-muted">{{ $s->cancelBlockedReason }}</small>
            @endif
        @endif

        <details class="mt-3 vip-help">
            <summary class="fs-13 fw-500">Как это работает</summary>
            <ul class="fs-13 text-muted ps-3 mt-2 mb-0">
                <li><strong>Включить:</strong> выберите срок и нажмите «Включить VIP». Деньги спишутся с баланса, VIP начнётся сразу.</li>
                <li><strong>Продлить:</strong> новый срок добавляется к текущему, оставшиеся дни не сгорают.</li>
                <li><strong>Отключить:</strong> в любой момент, вернём деньги за неиспользованные полные дни.</li>
                <li><strong>Не хватает денег?</strong> Пополните баланс картой, нужная сумма подставится сама.</li>
                <li>Пока товар не опубликован или не прошёл проверку, VIP не работает, но срок идёт.</li>
                <li>За сутки до окончания пришлём уведомление, по окончании VIP выключится сам.</li>
            </ul>
        </details>
    </div>
</div>

@include('backend.cabinet.vip.partials.confirm-script')
