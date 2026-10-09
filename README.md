# BIMM — локальный маркетплейс Приднестровья

Маркетплейс товаров и магазинов ПМР на Laravel 10: публичная витрина, кабинет продавца,
административная панель (Filament), AI-модерация товаров, заказы, VIP-размещение,
пополнение баланса банковской картой.

## Быстрый старт

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan bimm:work --serve
```

`bimm:work` запускает сайт, воркеры очередей и планировщик одной командой
(подробности — [docs/local_runtime_ru.md](docs/local_runtime_ru.md)). Сборка фронтенда не нужна:
стили и скрипты лежат в `public/`.

## Тесты

```bash
php artisan test
```

## Документация

Вся документация — в [docs/](docs/README.md): архитектура, эксплуатация, модерация,
заказы, правила дизайна. Код и тесты — источник истины; устаревшие планы лежат в `docs/archive`.

## Требования

PHP 8.1+, MySQL/MariaDB, Redis (в production), Meilisearch (необязательно, поиск по умолчанию идёт через БД).
