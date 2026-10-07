# Локальный запуск runtime-процессов

Актуализировано: 26.06.2026.

Для локальной разработки есть единая команда:

```bash
php artisan granat:work
```

Она запускает:

- worker очереди модерации товаров и изображений;
- worker очереди заказов и Telegram-уведомлений;
- Laravel Scheduler через `schedule:work`.

Если нужно одновременно поднять локальный сайт:

```bash
php artisan granat:work --serve
```

По умолчанию сайт запускается на `127.0.0.1:8000`. Хост и порт можно изменить:

```bash
php artisan granat:work --serve --host=127.0.0.1 --port=8001
```

Перед запуском можно посмотреть, какие процессы будут стартовать:

```bash
php artisan granat:work --dry-run --serve
```

## Что должно быть настроено

Для фоновой обработки:

```env
QUEUE_CONNECTION=database
PRODUCT_MODERATION_QUEUE=moderation
ORDER_NOTIFICATION_QUEUE=orders
```

Для Telegram-уведомлений:

```env
TELEGRAM_BOT_TOKEN=...
TELEGRAM_BOT_USERNAME=...
TELEGRAM_WEBHOOK_SECRET=...
```

Для ИИ-модерации нужны параметры провайдера из `config/moderation.php`.
Если ключи не настроены, worker запустится, но реальные запросы к провайдеру
будут падать.

## Production

`php artisan granat:work` предназначена для локальной разработки и ручной
проверки. На production процессы должны запускаться через Supervisor или
systemd, чтобы они автоматически поднимались после перезагрузки и
перезапускались после ошибки.

Готовые шаблоны Supervisor:

```text
deployment/supervisor/granat-moderation-worker.conf.example
deployment/supervisor/granat-orders-worker.conf.example
```

После deployment перезапускайте workers:

```bash
php artisan queue:restart
```
