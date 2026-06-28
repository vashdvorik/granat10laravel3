@extends('main_layots')

@section('title', 'Помощь продавцам | Как продавать на BIMM')

@section('content')
<main class="market-page seller-help-page">
    <section class="seller-help-hero">
        <div class="container container-240">
            <ul class="market-breadcrumbs" aria-label="Хлебные крошки">
                <li><a href="{{ route('home') }}">Главная</a></li>
                <li aria-current="page">Помощь продавцам</li>
            </ul>

            <div class="seller-help-hero__panel">
                <div>
                    <span class="market-eyebrow">Продавцам BIMM</span>
                    <h1>Ваш товар уже ищут. Пусть найдут его на BIMM.</h1>
                    <p>
                        BIMM помогает покупателям быстро понять, где рядом есть нужный товар,
                        увидеть магазин, цену, контакты и оформить заказ напрямую продавцу.
                    </p>
                    <div class="seller-help-hero__actions">
                        <a href="{{ route('register') }}" class="market-btn market-btn--primary">Стать продавцом</a>
                        <a href="{{ route('account.index') }}" class="market-btn market-btn--secondary">Войти в кабинет</a>
                    </div>
                </div>

                <div class="seller-help-hero__card" aria-label="Кратко о BIMM">
                    <strong>Что получает продавец</strong>
                    <ul>
                        <li>Витрину магазина с товарами и контактами.</li>
                        <li>Показы в каталоге, поиске и категориях.</li>
                        <li>Заявки от покупателей без сложной корзины.</li>
                        <li>Управление товарами из личного кабинета.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="seller-help-section" aria-labelledby="seller-help-start-title">
        <div class="container container-240">
            <div class="home-section__heading">
                <div>
                    <span class="market-eyebrow">Старт</span>
                    <h2 id="seller-help-start-title">Как подключиться</h2>
                </div>
            </div>

            <div class="seller-help-steps">
                <article>
                    <span>1</span>
                    <h3>Зарегистрируйтесь</h3>
                    <p>Создайте аккаунт продавца и войдите в кабинет. Это нужно, чтобы управлять магазином и товарами.</p>
                </article>
                <article>
                    <span>2</span>
                    <h3>Заполните магазин</h3>
                    <p>Укажите название, город, адрес, телефон и описание. Чем точнее данные, тем меньше лишних вопросов от покупателей.</p>
                </article>
                <article>
                    <span>3</span>
                    <h3>Добавьте товары</h3>
                    <p>Загрузите фото, название, цену, категорию, описание и условия получения. Товар должен быть понятен без переписки.</p>
                </article>
                <article>
                    <span>4</span>
                    <h3>Получайте обращения</h3>
                    <p>Покупатель звонит, открывает магазин или отправляет заказ. Дальше вы уточняете наличие и условия напрямую.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="seller-help-section seller-help-section--soft" aria-labelledby="seller-help-rules-title">
        <div class="container container-240">
            <div class="seller-help-grid">
                <div>
                    <span class="market-eyebrow">Качество карточек</span>
                    <h2 id="seller-help-rules-title">Что важно заполнить</h2>
                    <p>
                        Хорошая карточка товара снижает нагрузку на продавца: покупатель сразу видит,
                        подходит ли ему товар, где он находится и как связаться.
                    </p>
                </div>

                <div class="seller-help-checklist">
                    <div><i class="fa fa-check"></i><span>Реальная цена и актуальная скидка, если она есть.</span></div>
                    <div><i class="fa fa-check"></i><span>Фото товара без лишнего текста и водяных знаков.</span></div>
                    <div><i class="fa fa-check"></i><span>Понятное название: товар, модель, ключевые характеристики.</span></div>
                    <div><i class="fa fa-check"></i><span>Категория, город, адрес и рабочий номер телефона.</span></div>
                    <div><i class="fa fa-check"></i><span>Описание: комплектация, размер, состояние, гарантия, доставка.</span></div>
                </div>
            </div>
        </div>
    </section>

    <section class="seller-help-section" aria-labelledby="seller-help-orders-title">
        <div class="container container-240">
            <div class="seller-help-grid seller-help-grid--reverse">
                <div class="seller-help-card">
                    <h3>Как работает заказ</h3>
                    <p>
                        BIMM не принимает оплату на сайте. Заказ передаёт продавцу контакт покупателя,
                        товар, количество и комментарий. Оплата, наличие, доставка или самовывоз
                        согласовываются напрямую между покупателем и продавцом.
                    </p>
                </div>

                <div>
                    <span class="market-eyebrow">Заявки</span>
                    <h2 id="seller-help-orders-title">Что увидит продавец</h2>
                    <ul class="seller-help-list">
                        <li>Название товара и ссылку на карточку.</li>
                        <li>Имя и телефон покупателя.</li>
                        <li>Количество, способ получения и комментарий.</li>
                        <li>Дату создания заказа и статус в кабинете.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="seller-help-section seller-help-section--soft" aria-labelledby="seller-help-faq-title">
        <div class="container container-240">
            <div class="home-section__heading">
                <div>
                    <span class="market-eyebrow">Вопросы</span>
                    <h2 id="seller-help-faq-title">Часто спрашивают</h2>
                </div>
            </div>

            <div class="seller-help-faq">
                <details open>
                    <summary>Можно ли продавать без интернет-магазина?</summary>
                    <p>Да. BIMM даёт продавцу витрину с товарами, контактами и страницей магазина.</p>
                </details>
                <details>
                    <summary>Нужно ли постоянно быть онлайн?</summary>
                    <p>Нет, но телефон и данные магазина должны быть актуальными, чтобы покупатель мог быстро связаться.</p>
                </details>
                <details>
                    <summary>Что делать, если товара временно нет?</summary>
                    <p>Лучше скрыть товар или обновить описание. Неактуальные карточки снижают доверие покупателей.</p>
                </details>
                <details>
                    <summary>Зачем нужна модерация?</summary>
                    <p>Она помогает убрать запрещённые, некорректные и непонятные объявления, чтобы каталог был полезен покупателям.</p>
                </details>
            </div>
        </div>
    </section>

    <section class="seller-help-cta">
        <div class="container container-240">
            <div class="seller-help-cta__panel">
                <div>
                    <span class="market-eyebrow">Готовы начать?</span>
                    <h2>Добавьте магазин и товары — покупатели смогут найти вас через каталог и поиск.</h2>
                </div>
                <div class="seller-help-cta__actions">
                    <a href="{{ route('register') }}" class="market-btn market-btn--primary">Стать продавцом</a>
                    <a href="{{ route('shops') }}" class="market-btn market-btn--secondary">Посмотреть магазины</a>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection

@push('styles')
<style>
.seller-help-page{color:#222}.seller-help-hero{padding:30px 0 28px}.seller-help-hero__panel{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(280px,.65fr);gap:22px;align-items:stretch;padding:42px;border-radius:24px;background:linear-gradient(135deg,#f4eaff 0%,#edfaff 58%,#fff 100%)}.seller-help-hero h1{max-width:760px;margin:0 0 16px;font-size:44px;line-height:1.12;font-weight:700}.seller-help-hero p{max-width:680px;margin:0;color:#626678;font-size:18px;line-height:1.6}.seller-help-hero__actions,.seller-help-cta__actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:24px}.seller-help-hero__card,.seller-help-card{padding:24px;border:1px solid rgba(255,255,255,.9);border-radius:18px;background:rgba(255,255,255,.75);box-shadow:0 16px 38px rgba(74,42,104,.08)}.seller-help-hero__card strong{display:block;margin-bottom:14px;font-size:20px}.seller-help-hero__card ul,.seller-help-list{margin:0;padding:0;list-style:none}.seller-help-hero__card li,.seller-help-list li{position:relative;margin:0 0 12px;padding-left:22px;color:#555b6c;line-height:1.5}.seller-help-hero__card li:before,.seller-help-list li:before{content:"";position:absolute;left:0;top:.62em;width:8px;height:8px;border-radius:50%;background:#b965ed}.seller-help-section{padding:38px 0}.seller-help-section--soft{background:#faf8fc}.seller-help-steps{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}.seller-help-steps article{min-width:0;padding:24px;border:1px solid #e9e9ed;border-radius:16px;background:#fff}.seller-help-steps span{display:flex;align-items:center;justify-content:center;width:38px;height:38px;margin-bottom:18px;border-radius:50%;background:#26262c;color:#fff;font-weight:700}.seller-help-steps h3,.seller-help-card h3{margin:0 0 10px;font-size:19px}.seller-help-steps p,.seller-help-card p,.seller-help-grid p{margin:0;color:#626678;line-height:1.6}.seller-help-grid{display:grid;grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr);gap:28px;align-items:center}.seller-help-grid--reverse{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}.seller-help-grid h2,.seller-help-cta h2{margin:0 0 14px;font-size:30px;line-height:1.25}.seller-help-checklist{display:grid;gap:12px}.seller-help-checklist div{display:grid;grid-template-columns:34px minmax(0,1fr);gap:12px;align-items:center;padding:16px;border:1px solid #ebe8f0;border-radius:14px;background:#fff}.seller-help-checklist i{display:flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:10px;background:#f0e3fb;color:#9654c8}.seller-help-faq{display:grid;gap:12px}.seller-help-faq details{padding:18px 20px;border:1px solid #e9e9ed;border-radius:14px;background:#fff}.seller-help-faq summary{cursor:pointer;font-size:17px;font-weight:650}.seller-help-faq p{margin:12px 0 0;color:#626678;line-height:1.6}.seller-help-cta{padding:38px 0 58px}.seller-help-cta__panel{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:22px;align-items:center;padding:30px;border-radius:20px;background:#25252b;color:#fff}.seller-help-cta .market-eyebrow{color:#c993f1}.seller-help-cta h2{margin-bottom:0;color:#fff}.seller-help-cta .market-btn--primary{background:linear-gradient(90deg,#b965ed,#4ed9ee);color:#fff!important;box-shadow:0 10px 24px rgba(185,101,237,.28)}.seller-help-cta .market-btn--primary:hover,.seller-help-cta .market-btn--primary:focus{color:#fff!important;filter:brightness(1.04)}.seller-help-cta .market-btn--secondary{border-color:rgba(255,255,255,.22);background:#fff;color:#25252b!important}
@media(max-width:991px){.seller-help-hero__panel,.seller-help-grid,.seller-help-grid--reverse,.seller-help-cta__panel{grid-template-columns:1fr}.seller-help-steps{grid-template-columns:repeat(2,minmax(0,1fr))}.seller-help-cta__actions{margin-top:0}}
@media(max-width:767px){.seller-help-hero{padding:12px 0 18px}.seller-help-hero .container-240,.seller-help-section .container-240,.seller-help-cta .container-240{width:auto!important;max-width:none!important;margin:0!important;padding:0 15px!important}.seller-help-hero__panel{padding:28px 18px;border-radius:16px}.seller-help-hero h1{font-size:30px}.seller-help-hero p{font-size:15px}.seller-help-hero__actions,.seller-help-cta__actions{display:grid;grid-template-columns:1fr}.seller-help-section{padding:28px 0}.seller-help-steps{grid-template-columns:1fr}.seller-help-grid h2,.seller-help-cta h2{font-size:24px}.seller-help-checklist div{align-items:flex-start}.seller-help-cta{padding:28px 0 96px}.seller-help-cta__panel{padding:24px 18px;border-radius:16px}}
</style>
@endpush
