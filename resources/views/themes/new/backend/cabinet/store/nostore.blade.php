<div class="nostore-page">
    <div class="nostore-hero">
        <div>
            <span class="badge badge-round badge-primary mb-2">Магазин не создан</span>
            <h1 class="h3">Создайте магазин и начните продавать на BIMM</h1>
            <p>Чтобы публиковать товары, принимать заказы и управлять видимостью магазина, сначала нужно создать магазин и заполнить базовые данные.</p>

            <div class="nostore-hero__actions">
                <a href="{{ route('account.store.create') }}" class="btn btn-primary btn-squared">
                    Создать магазин
                </a>
            </div>
        </div>

        <div class="nostore-hero__shape">
            <img src="{{ asset('cabinet/img/svg/banne-group21.svg') }}" alt="Создание магазина" class="svg">
        </div>
    </div>

    <div class="nostore-grid">
        <div class="nostore-card">
            <div class="nostore-card__icon nostore-card__icon--primary">
                <img src="{{ asset('cabinet/img/svg/idea.svg') }}" alt="" class="svg">
            </div>
            <h5>Что делать дальше</h5>
            <p>Заполните название, телефон, адрес и загрузите логотип. После этого можно добавить первый товар.</p>
        </div>

        <div class="nostore-card">
            <div class="nostore-card__icon nostore-card__icon--info">
                <img src="{{ asset('cabinet/img/svg/chat.svg') }}" alt="" class="svg">
            </div>
            <h5>Если нужна помощь</h5>
            <p>Проверьте подсказки в кабинете: они подскажут, что нужно сделать, чтобы товары стали видны покупателям.</p>
        </div>

        <div class="nostore-card">
            <div class="nostore-card__icon nostore-card__icon--success">
                <img src="{{ asset('cabinet/img/svg/documents.svg') }}" alt="" class="svg">
            </div>
            <h5>Почему это важно</h5>
            <p>Без магазина покупатели не увидят товары, а также не смогут перейти к контактам и заказам.</p>
        </div>
    </div>
</div>
