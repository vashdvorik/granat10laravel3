{{-- Форма заказа на странице оформления (orders/create). Общая для всех тем оформления:
     цвета берутся из переменных --ob-* (темы переопределяют их ниже).
     Необязательные переменные: $orderBoxTitle, $orderBoxLead — заголовок и подпись над формой. --}}
@php
    $obUnitPrice = (float) ($currentPrice ?? $product->price);
    $obPhone = trim((string) $product->seller->phone);
    $obPhoneHref = preg_replace('/[^\d+]/', '', $obPhone);
    $obQuantity = max(1, min(99, (int) old('quantity', 1)));
    $obDelivery = old('fulfillment_method', 'pickup') === 'delivery' && $orderAvailability->deliveryAllowed;
    $obFormat = static fn (float $value): string => number_format($value, 0, '.', ' ');
    $obTitle = $orderBoxTitle ?? 'Оформить заказ';
    $obLead = $orderBoxLead ?? 'Оставьте имя и телефон: продавец позвонит, подтвердит наличие и договорится с вами о получении.';
@endphp

<section class="order-box" id="order-form" aria-labelledby="order-box-title" data-unit-price="{{ $obUnitPrice }}">
    <header class="order-box__head">
        <h2 id="order-box-title">{{ $obTitle }}</h2>
        <p>{{ $obLead }}</p>
    </header>

    @if($errors->has('order'))
        <div class="order-alert" role="alert">{{ $errors->first('order') }}</div>
    @elseif($errors->any())
        <div class="order-alert" role="alert">Проверьте, пожалуйста, подсвеченные поля ниже.</div>
    @endif

    <form method="POST" action="{{ route('orders.store', $product) }}" class="order-form-body" data-order-form>
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ $orderIdempotencyKey }}">

        <div class="order-field">
            <label for="order-name">Ваше имя</label>
            <input id="order-name" type="text" name="customer_name" value="{{ old('customer_name') }}" maxlength="100"
                   autocomplete="name" placeholder="Как к вам обращаться" required>
            @error('customer_name') <span class="order-error">{{ $message }}</span> @enderror
        </div>

        <div class="order-field">
            <label for="order-phone">Телефон</label>
            <input id="order-phone" type="tel" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="30"
                   autocomplete="tel" inputmode="tel" placeholder="+373 ___ __ ___" required>
            @error('customer_phone') <span class="order-error">{{ $message }}</span> @enderror
            <span class="order-hint">На этот номер позвонит продавец.</span>
        </div>

        <div class="order-row">
            <div class="order-field">
                <label for="order-quantity">Количество</label>
                <div class="order-qty">
                    <button type="button" data-qty-step="-1" aria-label="Уменьшить количество">−</button>
                    <input id="order-quantity" type="number" name="quantity" value="{{ $obQuantity }}" min="1" max="99"
                           inputmode="numeric" required>
                    <button type="button" data-qty-step="1" aria-label="Увеличить количество">+</button>
                </div>
                @error('quantity') <span class="order-error">{{ $message }}</span> @enderror
            </div>

            <div class="order-total" aria-live="polite">
                <span>Итого</span>
                <strong data-order-total>{{ $obFormat($obUnitPrice * $obQuantity) }} ₽</strong>
            </div>
        </div>

        @if($orderAvailability->deliveryAllowed)
            <fieldset class="order-methods">
                <legend>Как получить товар</legend>
                <label class="order-method">
                    <input type="radio" name="fulfillment_method" value="pickup" @checked(! $obDelivery)>
                    <span><b>Самовывоз</b><small>Заберёте у продавца</small></span>
                </label>
                <label class="order-method">
                    <input type="radio" name="fulfillment_method" value="delivery" @checked($obDelivery)>
                    <span><b>Доставка</b><small>Привезёт продавец</small></span>
                </label>
                @error('fulfillment_method') <span class="order-error">{{ $message }}</span> @enderror
            </fieldset>

            <div class="order-field order-address" data-delivery-address @unless($obDelivery) hidden @endunless>
                @if(filled($product->seller->delivery_terms))
                    <div class="order-delivery-terms">
                        <strong>Условия доставки</strong>
                        <p>{{ $product->seller->delivery_terms }}</p>
                    </div>
                @endif
                <label for="order-address">Адрес доставки</label>
                <textarea id="order-address" name="delivery_address" maxlength="500" rows="2"
                          placeholder="Город, улица, дом, квартира">{{ old('delivery_address') }}</textarea>
                @error('delivery_address') <span class="order-error">{{ $message }}</span> @enderror
            </div>
        @else
            {{-- Доставки нет: способ получения один, спрашивать его незачем --}}
            <input type="hidden" name="fulfillment_method" value="pickup">
            <p class="order-pickup"><i class="fa-solid fa-store" aria-hidden="true"></i>
                <span><b>Самовывоз у продавца.</b>@if(filled($product->seller->address)) {{ $product->seller->address }}@endif</span>
            </p>
        @endif

        <details class="order-comment" @if(old('customer_comment') || $errors->has('customer_comment')) open @endif>
            <summary>Добавить комментарий продавцу</summary>
            <textarea name="customer_comment" maxlength="1000" rows="3" aria-label="Комментарий продавцу"
                      placeholder="Например, удобное время звонка">{{ old('customer_comment') }}</textarea>
            @error('customer_comment') <span class="order-error">{{ $message }}</span> @enderror
        </details>

        <label class="order-consent">
            <input type="checkbox" name="consent" value="1" @checked(old('consent')) required>
            <span>Согласен на передачу имени и телефона продавцу для выполнения заказа.</span>
        </label>
        @error('consent') <span class="order-error">{{ $message }}</span> @enderror

        <button type="submit" class="order-submit" data-order-submit>Отправить заказ</button>
    </form>

    <ol class="order-steps" aria-label="Что будет дальше">
        <li><b>1</b><span>Вы отправляете заявку</span></li>
        <li><b>2</b><span>Продавец звонит и подтверждает наличие и цену</span></li>
        <li><b>3</b><span>Забираете товар и платите продавцу напрямую</span></li>
    </ol>

    @if($obPhone !== '')
        <p class="order-call">Хотите сначала уточнить? <a href="tel:{{ $obPhoneHref }}"><i class="fa-solid fa-phone" aria-hidden="true"></i> {{ $obPhone }}</a></p>
    @endif
</section>

<style>
.order-box{--ob-grad:linear-gradient(90deg,#b965ed,#4ed9ee);--ob-accent:#8750b4;--ob-focus:#b965ed;--ob-ink:#222;--ob-muted:#767676;--ob-line:#e4e4ea;--ob-soft:#f6f3fb;--ob-radius:14px;margin:0;padding:22px;border:1px solid var(--ob-line);border-radius:var(--ob-radius);background:#fff;box-shadow:0 10px 30px rgba(40,30,60,.07);color:var(--ob-ink);scroll-margin-top:90px}
.theme-new .order-box{--ob-grad:var(--bimm-gold-dark,#151515);--ob-accent:var(--bimm-gold,#555dff);--ob-focus:var(--bimm-gold,#555dff);--ob-line:var(--bimm-line,#e6e6e6);--ob-soft:#f4f5ff;--ob-radius:var(--bimm-radius,18px)}
.theme-joom .order-box{--ob-grad:var(--joom-red,#555dff);--ob-accent:var(--joom-red,#555dff);--ob-focus:var(--joom-red,#555dff);--ob-line:var(--joom-line,#e6e8eb);--ob-soft:var(--joom-primary-soft,#f0efff);--ob-radius:15px}
.order-box__head h2{margin:0 0 6px;font-size:22px;line-height:1.2;font-weight:700}
.order-box__head p{margin:0 0 18px;color:var(--ob-muted);font-size:14px;line-height:1.5}
.order-form-body{display:grid;gap:14px}
.order-field{display:flex;flex-direction:column;gap:6px;min-width:0;margin:0}
.order-field>label,.order-methods legend{margin:0;font-size:13px;font-weight:600;color:var(--ob-ink)}
.order-field input[type=text],.order-field input[type=tel],.order-field textarea,.order-comment textarea{width:100%;padding:12px 14px;border:1px solid var(--ob-line);border-radius:10px;background:#fff;font-size:15px;color:var(--ob-ink);transition:border-color .15s,box-shadow .15s}
.order-field input:focus,.order-field textarea:focus,.order-comment textarea:focus,.order-qty input:focus{outline:0;border-color:var(--ob-focus);box-shadow:0 0 0 3px color-mix(in srgb,var(--ob-focus) 18%,transparent)}
.order-hint{font-size:12px;color:var(--ob-muted)}
.order-row{display:flex;align-items:flex-end;justify-content:space-between;gap:16px}
.order-qty{display:inline-flex;align-items:stretch;border:1px solid var(--ob-line);border-radius:10px;overflow:hidden;background:#fff}
.order-qty button{width:42px;border:0;background:var(--ob-soft);color:var(--ob-accent);font-size:20px;line-height:1;cursor:pointer}
.order-qty button:hover,.order-qty button:focus{background:color-mix(in srgb,var(--ob-accent) 14%,#fff);outline:0}
.order-qty input{width:56px;padding:11px 0;border:0;border-radius:0;text-align:center;font-size:16px;font-weight:600;-moz-appearance:textfield;appearance:textfield}
.order-qty input::-webkit-outer-spin-button,.order-qty input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.order-total{display:flex;flex-direction:column;align-items:flex-end;gap:2px;text-align:right}
.order-total span{font-size:12px;color:var(--ob-muted)}
.order-total strong{font-size:24px;line-height:1.1;font-weight:700;white-space:nowrap}
.order-methods{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:0;padding:0;border:0}
.order-methods legend{grid-column:1/-1;float:none;width:auto;padding:0;margin-bottom:2px;border:0}
.order-method{position:relative;display:flex;margin:0;cursor:pointer}
.order-method input{position:absolute;opacity:0;pointer-events:none}
.order-method span{display:flex;flex-direction:column;gap:2px;width:100%;padding:12px 14px;border:1px solid var(--ob-line);border-radius:10px;background:#fff;transition:border-color .15s,background-color .15s}
.order-method b{font-size:14px}.order-method small{font-size:12px;color:var(--ob-muted)}
.order-method input:checked+span{border-color:var(--ob-accent);background:var(--ob-soft);box-shadow:inset 0 0 0 1px var(--ob-accent)}
.order-method input:focus-visible+span{outline:2px solid var(--ob-focus);outline-offset:2px}
.order-address[hidden]{display:none}
.order-delivery-terms{margin:0 0 4px;padding:12px 14px;border:1px solid var(--ob-line);border-radius:10px;background:var(--ob-soft);color:#444;font-size:13px}
.order-delivery-terms strong{display:block;margin-bottom:4px;color:var(--ob-ink)}.order-delivery-terms p{margin:0;white-space:pre-line}
.order-pickup{display:flex;gap:10px;align-items:flex-start;margin:0;padding:12px 14px;border:1px solid var(--ob-line);border-radius:10px;background:var(--ob-soft);font-size:14px}
.order-pickup i{margin-top:3px;color:var(--ob-accent)}
.order-comment summary{display:inline-block;cursor:pointer;color:var(--ob-accent);font-size:14px;font-weight:600;list-style:none}
.order-comment summary::-webkit-details-marker{display:none}
.order-comment summary:before{content:"+ "}.order-comment[open] summary:before{content:"− "}
.order-comment textarea{margin-top:10px}
.order-consent{display:flex;align-items:flex-start;gap:10px;margin:2px 0 0;font-size:12.5px;line-height:1.45;color:var(--ob-muted);cursor:pointer}
.order-consent input{flex:0 0 auto;width:18px;height:18px;margin:1px 0 0;accent-color:var(--ob-accent)}
.order-submit{display:flex;align-items:center;justify-content:center;width:100%;min-height:54px;padding:0 24px;border:0;border-radius:12px;background:var(--ob-grad);color:#fff;font-size:17px;font-weight:700;cursor:pointer;box-shadow:0 8px 20px rgba(60,40,90,.18);transition:transform .15s,box-shadow .15s,opacity .15s}
.order-submit:hover,.order-submit:focus{transform:translateY(-1px);box-shadow:0 12px 24px rgba(60,40,90,.24);outline:0}
.order-submit[disabled]{opacity:.65;cursor:progress;transform:none}
.order-steps{display:grid;gap:10px;margin:20px 0 0;padding:16px 0 0;border-top:1px solid var(--ob-line);list-style:none}
.order-steps li{display:flex;align-items:center;gap:12px;font-size:13px;color:var(--ob-muted)}
.order-steps b{display:inline-flex;align-items:center;justify-content:center;flex:0 0 24px;width:24px;height:24px;border-radius:50%;background:var(--ob-soft);color:var(--ob-accent);font-size:12px}
.order-call{margin:14px 0 0;font-size:13px;color:var(--ob-muted)}.order-call a{color:var(--ob-accent);font-weight:700;white-space:nowrap}
.order-error{color:#b42318;font-size:13px}
.order-alert{margin:0 0 14px;padding:12px 14px;border-radius:10px;background:#fff1f0;color:#b42318;font-size:14px}
.order-field input:invalid:not(:placeholder-shown):not(:focus){border-color:#e0a39d}
@media(max-width:767px){.order-box{padding:18px 16px}.order-box__head h2{font-size:20px}.order-total strong{font-size:22px}}
@media(max-width:380px){.order-methods{grid-template-columns:1fr}}
</style>

<script>
(function () {
    var box = document.getElementById('order-form');
    if (!box) return;

    var form = box.querySelector('[data-order-form]');
    var qty = box.querySelector('input[name="quantity"]');
    var total = box.querySelector('[data-order-total]');
    var unit = parseFloat(box.getAttribute('data-unit-price')) || 0;

    // Количество: кнопки «−» и «+», итоговая сумма пересчитывается сразу
    function clampQty() {
        var value = parseInt(qty.value, 10);
        if (!value || value < 1) value = 1;
        if (value > 99) value = 99;
        return value;
    }
    function updateTotal() {
        var value = clampQty();
        total.textContent = new Intl.NumberFormat('ru-RU').format(Math.round(unit * value)).replace(/ /g, ' ') + ' ₽';
    }
    box.querySelectorAll('[data-qty-step]').forEach(function (button) {
        button.addEventListener('click', function () {
            qty.value = Math.min(99, Math.max(1, clampQty() + parseInt(button.getAttribute('data-qty-step'), 10)));
            updateTotal();
        });
    });
    qty.addEventListener('input', updateTotal);
    qty.addEventListener('blur', function () { qty.value = clampQty(); updateTotal(); });
    updateTotal();

    // Адрес спрашиваем только при доставке
    var address = box.querySelector('[data-delivery-address]');
    if (address) {
        var textarea = address.querySelector('textarea');
        var sync = function () {
            var checked = box.querySelector('input[name="fulfillment_method"]:checked');
            var delivery = !!checked && checked.value === 'delivery';
            address.hidden = !delivery;
            textarea.required = delivery;
        };
        box.querySelectorAll('input[name="fulfillment_method"]').forEach(function (radio) {
            radio.addEventListener('change', sync);
        });
        sync();
    }

    // Защита от двойной отправки
    var submitButton = box.querySelector('[data-order-submit]');
    var submitLabel = submitButton.textContent;
    form.addEventListener('submit', function () {
        if (form.checkValidity()) {
            submitButton.disabled = true;
            submitButton.textContent = 'Отправляем…';
        }
    });
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            submitButton.disabled = false;
            submitButton.textContent = submitLabel;
        }
    });
})();
</script>
