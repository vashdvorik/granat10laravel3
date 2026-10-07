# Система заказов

Актуализировано: 25.06.2026.

## Назначение

Покупатель может без регистрации оформить заказ на один товар одного продавца.
Корзины и онлайн-оплаты нет. Оплата, доставка и окончательные условия
согласовываются напрямую с продавцом.

## Доступность заказа

Форма отображается только когда `OrderAvailabilityService` подтверждает:

- товар опубликован и имеет актуальное одобрение модерации;
- у товара включён `can_order`;
- у продавца включён `can_order`;
- товар относится к выбранному региону.

Доставка доступна только при одновременном включении у продавца и товара.

## Публичный сценарий

1. `GET /product/{alias}` показывает карточку и форму.
2. `POST /product/{product}/order` принимает `StoreOrderRequest`.
3. Сервер самостоятельно определяет продавца, цену и снимок товара.
4. Идемпотентный UUID защищает от повторной отправки формы.
5. После создания покупатель перенаправляется на
   `GET /orders/{publicId}`.
6. Доступ к результату дополнительно защищён токеном в сессии.

История заказов и персональные данные не сохраняются в `localStorage`.

## Поля формы

- имя;
- телефон;
- количество;
- самовывоз или доставка;
- адрес при доставке;
- необязательный комментарий;
- согласие на обработку данных;
- серверный `idempotency_key`.

## Статусы

Коды определены enum `App\Enums\OrderStatus` и не являются произвольным
справочником:

| Код | Название |
|---|---|
| `new` | Новый |
| `accepted` | Принят |
| `processing` | В работе |
| `ready_for_pickup` | Готов к самовывозу |
| `out_for_delivery` | Передан в доставку |
| `completed` | Выполнен |
| `cancelled` | Отменён |

Допустимые переходы задаются методом `allowedTransitions()`. Из финальных
статусов обычный переход запрещён.

## Кабинет и Filament

- продавец видит только заказы своего магазина;
- изменение статуса проходит через `OrderManagementService`;
- администратор видит все заказы в `OrderResource`;
- история переходов хранится в `order_status_histories`;
- создание и изменение заказа отправляет уведомления после commit;
- Telegram используется только при подключённом и проверенном чате продавца.

## Персональные данные

Команда `orders:anonymize-expired` очищает персональные данные завершённых и
отменённых заказов после срока из `config/privacy.php`. Финансовый снимок,
товар, статусы и аудит сохраняются.

Scheduler должен запускаться каждую минуту. Перед ручным запуском используйте:

```bash
php artisan orders:anonymize-expired --dry-run
```

## Ключевые файлы

- `app/Services/OrderAvailabilityService.php`
- `app/Services/OrderService.php`
- `app/Services/OrderManagementService.php`
- `app/Services/OrderAnonymizationService.php`
- `app/Enums/OrderStatus.php`
- `app/Http/Requests/StoreOrderRequest.php`
- `resources/views/orders/`
- `resources/views/backend/cabinet/orders/`
- `app/Filament/Resources/OrderResource.php`

## Проверка

Основные тесты:

```bash
php artisan test tests/Feature/PublicOrderFlowTest.php
php artisan test tests/Feature/OrderServiceTest.php
php artisan test tests/Feature/OrderManagementServiceTest.php
php artisan test tests/Feature/OrderAnonymizationTest.php
php artisan test tests/Feature/OrderNotificationTest.php
```

