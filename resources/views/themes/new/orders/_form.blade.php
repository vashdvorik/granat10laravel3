<section class="bimm-order-form" id="order-form" style="padding:24px;border:1px solid var(--bimm-line);border-radius:var(--bimm-radius);background:#fff">
    <span class="bimm-kicker">Заявка продавцу</span>
    <h2 style="margin:0 0 8px;font-size:25px">Оформить заказ у продавца</h2><p style="margin:0 0 20px;color:var(--bimm-muted);font-size:13px">Оставьте контакты — продавец подтвердит наличие, цену и способ получения.</p>
    @if($errors->has('order'))<div class="bimm-flash" style="color:#8b3232;background:#fbe3df">{{ $errors->first('order') }}</div>@endif
    <form method="POST" action="{{ route('orders.store', $product) }}">
        @csrf<input type="hidden" name="idempotency_key" value="{{ $orderIdempotencyKey }}">
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <label>Ваше имя<input style="width:100%;margin-top:6px;padding:11px;border:1px solid var(--bimm-line);border-radius:10px" type="text" name="customer_name" value="{{ old('customer_name') }}" maxlength="100" autocomplete="name" required></label>
            <label>Телефон для связи<input style="width:100%;margin-top:6px;padding:11px;border:1px solid var(--bimm-line);border-radius:10px" type="tel" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="30" autocomplete="tel" placeholder="+373 ..." required></label>
            <label>Количество<input style="width:100%;margin-top:6px;padding:11px;border:1px solid var(--bimm-line);border-radius:10px" type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="99" required></label>
            <fieldset style="border:0;padding:0;margin:0"><legend>Как получить товар</legend><label style="display:block;margin-top:10px"><input type="radio" name="fulfillment_method" value="pickup" @checked(old('fulfillment_method', 'pickup') === 'pickup')> Самовывоз</label>@if($orderAvailability->deliveryAllowed)<label style="display:block;margin-top:8px"><input type="radio" name="fulfillment_method" value="delivery" @checked(old('fulfillment_method') === 'delivery')> Доставка</label>@endif</fieldset>
            @if($orderAvailability->deliveryAllowed)<label style="grid-column:1/-1" data-delivery-address>
                @if(filled($product->seller->delivery_terms))
                    <div style="margin:0 0 6px;padding:14px 16px;border:1px solid var(--bimm-line);border-radius:10px;background:#fafafa;color:var(--bimm-text)">
                        <strong style="display:block;margin-bottom:5px">Условия доставки</strong>
                        <div style="white-space:pre-line">{{ $product->seller->delivery_terms }}</div>
                    </div>
                @endif
                Адрес доставки
                <textarea style="display:block;width:100%;margin-top:6px;padding:11px;border:1px solid var(--bimm-line);border-radius:10px" name="delivery_address" maxlength="500" rows="2">{{ old('delivery_address') }}</textarea>
            </label>@endif
            <label style="grid-column:1/-1">Комментарий продавцу <span class="bimm-form-optional">необязательно</span><textarea style="display:block;width:100%;margin-top:6px;padding:11px;border:1px solid var(--bimm-line);border-radius:10px" name="customer_comment" maxlength="1000" rows="3" placeholder="Например: удобное время для звонка">{{ old('customer_comment') }}</textarea></label>
        </div>
        <label style="display:flex;gap:8px;align-items:flex-start;margin:18px 0;font-size:12px"><input type="checkbox" name="consent" value="1" @checked(old('consent')) required> Согласен(на) передать эти данные продавцу для обработки заявки.</label>
        <button class="bimm-button bimm-button--gold" type="submit">Отправить заявку продавцу <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
    </form>
</section>
@if($orderAvailability->deliveryAllowed)<script>
document.querySelectorAll('input[name="fulfillment_method"]').forEach(function(input){
    input.addEventListener('change',function(){
        var field=document.querySelector('[data-delivery-address]');
        if(field)field.style.display=this.value==='delivery'?'flex':'none';
    });
});
var selectedFulfillment=document.querySelector('input[name="fulfillment_method"]:checked');
if(selectedFulfillment)selectedFulfillment.dispatchEvent(new Event('change'));
</script>@endif
