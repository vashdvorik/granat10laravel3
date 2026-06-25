<section class="order-form" id="order-form">
    <h2>Оформить заказ</h2>

    @if($errors->has('order'))
        <div class="order-alert">{{ $errors->first('order') }}</div>
    @endif

    <form method="POST" action="{{ route('orders.store', $product) }}">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ $orderIdempotencyKey }}">

        <div class="order-grid">
            <label>
                Ваше имя
                <input type="text" name="customer_name" value="{{ old('customer_name') }}" maxlength="100"
                    autocomplete="name" required>
                @error('customer_name') <span class="order-error">{{ $message }}</span> @enderror
            </label>

            <label>
                Телефон
                <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="30"
                    autocomplete="tel" inputmode="tel" required>
                @error('customer_phone') <span class="order-error">{{ $message }}</span> @enderror
            </label>

            <label>
                Количество
                <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="99" required>
                @error('quantity') <span class="order-error">{{ $message }}</span> @enderror
            </label>

            <fieldset>
                <legend>Способ получения</legend>
                <label class="order-choice">
                    <input type="radio" name="fulfillment_method" value="pickup"
                        @checked(old('fulfillment_method', 'pickup') === 'pickup')>
                    Самовывоз
                </label>
                @if($orderAvailability->deliveryAllowed)
                    <label class="order-choice">
                        <input type="radio" name="fulfillment_method" value="delivery"
                            @checked(old('fulfillment_method') === 'delivery')>
                        Доставка продавцом
                    </label>
                @endif
                @error('fulfillment_method') <span class="order-error">{{ $message }}</span> @enderror
            </fieldset>

            @if($orderAvailability->deliveryAllowed)
                <label class="order-wide" data-delivery-address>
                    Адрес доставки
                    <textarea name="delivery_address" maxlength="500" rows="2">{{ old('delivery_address') }}</textarea>
                    @error('delivery_address') <span class="order-error">{{ $message }}</span> @enderror
                </label>
            @endif

            <label class="order-wide">
                Комментарий продавцу
                <textarea name="customer_comment" maxlength="1000" rows="3">{{ old('customer_comment') }}</textarea>
                @error('customer_comment') <span class="order-error">{{ $message }}</span> @enderror
            </label>
        </div>

        <label class="order-consent">
            <input type="checkbox" name="consent" value="1" @checked(old('consent')) required>
            Я согласен на обработку данных для передачи заказа продавцу.
        </label>
        @error('consent') <span class="order-error">{{ $message }}</span> @enderror

        <button type="submit" class="btn-add-cart order-submit">Отправить заказ</button>
        <p class="order-note">Оплата и условия получения согласовываются напрямую с продавцом.</p>
    </form>
</section>

<style>
.order-form{margin-top:24px;padding:28px;border:1px solid #e8e8e8;border-radius:12px;background:#fff;box-shadow:0 12px 35px rgba(0,0,0,.05)}
.order-form h2{margin:0 0 18px;font-size:22px}.order-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.order-grid label,.order-grid fieldset{display:flex;flex-direction:column;gap:7px;margin:0}.order-grid fieldset{border:0;padding:0}
.order-grid input[type=text],.order-grid input[type=tel],.order-grid input[type=number],.order-grid textarea{width:100%;border:1px solid #ddd;border-radius:4px;padding:10px}
.order-wide{grid-column:1/-1}.order-choice,.order-consent{display:flex!important;flex-direction:row!important;align-items:center;gap:8px}
.order-consent{margin:18px 0 8px}.order-submit{display:inline-block;border:0;border-radius:7px;padding:13px 26px;background:linear-gradient(90deg,#b965ed,#4ed9ee);color:#fff;font-weight:600}.order-note{margin:10px 0 0;color:#666}
.order-error{color:#b42318;font-size:13px}.order-alert{margin-bottom:16px;padding:12px;background:#fff1f0;color:#b42318;border-radius:4px}
@media(max-width:767px){.order-form{padding:22px 18px}.order-grid{grid-template-columns:1fr}.order-wide{grid-column:auto}.order-submit{width:100%}}
</style>

@if($orderAvailability->deliveryAllowed)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const address = document.querySelector('[data-delivery-address]');
    const radios = document.querySelectorAll('input[name="fulfillment_method"]');
    const updateAddress = () => {
        const delivery = document.querySelector('input[name="fulfillment_method"]:checked')?.value === 'delivery';
        address.style.display = delivery ? 'flex' : 'none';
        address.querySelector('textarea').required = delivery;
    };
    radios.forEach((radio) => radio.addEventListener('change', updateAddress));
    updateAddress();
});
</script>
@endif
