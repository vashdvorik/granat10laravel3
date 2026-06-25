# Краткое развёртывание на production-сервере

## 1. Требования

- Linux, Nginx или Apache;
- PHP 8.1+ с расширениями: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `gd`;
- MySQL/MariaDB;
- Composer 2;
- Node.js 18+ и npm;
- корневая директория сайта должна указывать на `public`.

Перед развёртыванием сделайте резервную копию базы данных и `storage/app/public`.

Пример для MySQL:

```bash
mkdir -p /var/backups/granat
mysqldump --single-transaction --routines --triggers \
  -u database_user -p database_name \
  | gzip > /var/backups/granat/database-$(date +%F-%H%M).sql.gz

tar -czf /var/backups/granat/public-storage-$(date +%F-%H%M).tar.gz \
  -C /var/www/granat storage/app/public
```

Проверьте, что архивы созданы и не пустые:

```bash
ls -lh /var/backups/granat
gzip -t /var/backups/granat/database-*.sql.gz
tar -tzf /var/backups/granat/public-storage-*.tar.gz > /dev/null
```

Копии должны храниться вне сервера приложения. Периодически проверяйте
восстановление на отдельной тестовой базе. Не храните пароль базы в команде или
репозитории — используйте защищённый клиентский файл MySQL либо ввод пароля.

## 2. Установка проекта

```bash
git clone <URL_РЕПОЗИТОРИЯ> /var/www/granat
cd /var/www/granat

composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build

cp .env.example .env
php artisan key:generate
```

Заполните `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=database_name
DB_USERNAME=database_user
DB_PASSWORD=strong_password

FILESYSTEM_DISK=public
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_CONNECTION=session
QUEUE_CONNECTION=redis
FILAMENT_PANEL_ENABLED=true
PRODUCT_MODERATION_QUEUE=moderation
ORDER_NOTIFICATION_QUEUE=orders
TELEGRAM_BOT_TOKEN=токен_от_BotFather
TELEGRAM_BOT_USERNAME=имя_бота_без_@
TELEGRAM_WEBHOOK_SECRET=случайная_секретная_строка
TELEGRAM_TIMEOUT=10
PRODUCT_MODERATION_MAX_ATTEMPTS=3
PRODUCT_MODERATION_TIMEOUT=60
HEALTH_WORKER_MAX_AGE=180
ORDER_PERSONAL_DATA_RETENTION_DAYS=365
ORDER_ANONYMIZATION_BATCH_SIZE=200

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2
REDIS_QUEUE_DB=3
REDIS_QUEUE_CONNECTION=queue
REDIS_QUEUE=moderation
REDIS_QUEUE_RETRY_AFTER=120
REDIS_QUEUE_BLOCK_FOR=5

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=user
MAIL_PASSWORD=password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

## 3. База данных и файлы

```bash
php artisan migrate --force
php artisan storage:link

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

Не запускайте `migrate:fresh` или полный набор тестовых seeders на production.

Если разворачивается новая пустая база, отдельно импортируйте справочники категорий и тарифов:

```bash
php artisan db:seed --class=CategoriesJsonSeeder --force
php artisan db:seed --class=PackagesTableSeeder --force
```

`CategoriesJsonSeeder` идемпотентен: он сохраняет фиксированные ID и alias категорий, восстанавливает Nested Set и пересобирает кеш каталога.

`PackagesTableSeeder` предварительно очищает таблицу тарифов, поэтому запускайте его только для новой пустой базы. На существующей рабочей базе не запускайте этот seeder и обязательно делайте резервную копию перед любым импортом.

## 4. Первый администратор

Временно добавьте в `.env`:

```env
ADMIN_NAME=Administrator
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=strong-admin-password
```

Затем выполните:

```bash
php artisan config:clear
php artisan db:seed --class=AdminRoleSeeder --force
```

После создания администратора удалите `ADMIN_PASSWORD` из `.env`.

Адрес Filament:

```text
https://example.com/admin/login
```

Административная панель `/admin` обслуживается только Filament. Voyager удалён из приложения.

## 5. Production-кеш

```bash
php artisan optimize:clear
php artisan optimize
php artisan filament:optimize
```

После изменения `.env` обязательно повторите эти команды.

## 6. Планировщик

Добавьте в `crontab` пользователя веб-сервера:

```cron
* * * * * cd /var/www/granat && php artisan schedule:run >> /dev/null 2>&1
```

Планировщик ежедневно проверяет истёкшие тарифы и автопродление, а в 02:00
анонимизирует персональные данные завершённых и отменённых заказов старше
`ORDER_PERSONAL_DATA_RETENTION_DAYS`.

Проверка без изменения данных:

```bash
php artisan orders:anonymize-expired --dry-run
```

## 7. Обновление приложения

```bash
cd /var/www/granat
php artisan down

git pull
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan filament:optimize

php artisan up
```

Перед `php artisan up` проверьте:

```bash
php artisan about
php artisan route:list --path=admin
tail -n 100 storage/logs/laravel.log
```

Для production используйте ротацию журналов и отключённый debug:

```env
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=daily
LOG_LEVEL=warning
MODERATION_LOG_LEVEL=info
MODERATION_LOG_DAYS=30
```

## 8. Постоянный worker модерации

При `QUEUE_CONNECTION=redis` одного запуска команды в терминале недостаточно. Worker должен автоматически запускаться после перезагрузки сервера и перезапускаться после ошибки.

Готовый шаблон находится в:

```text
deployment/supervisor/granat-moderation-worker.conf.example
deployment/supervisor/granat-orders-worker.conf.example
```

Скопируйте его и проверьте пути к PHP и проекту:

```bash
sudo cp deployment/supervisor/granat-moderation-worker.conf.example /etc/supervisor/conf.d/granat-moderation-worker.conf
sudo cp deployment/supervisor/granat-orders-worker.conf.example /etc/supervisor/conf.d/granat-orders-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start granat-moderation-worker:*
sudo supervisorctl start granat-orders-worker:*
```

Зарегистрировать Telegram webhook после настройки HTTPS:

```bash
curl -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/setWebhook" \
  -d "url=https://example.com/webhooks/telegram" \
  -d "secret_token=${TELEGRAM_WEBHOOK_SECRET}"
```

Webhook должен использовать только HTTPS. `TELEGRAM_WEBHOOK_SECRET` нельзя
публиковать или передавать продавцам.
sudo supervisorctl status
```

После каждого обновления приложения перезапускайте worker:

```bash
php artisan queue:restart
```

Проверка готовности:

```bash
curl -f https://example.com/health/live
curl -f https://example.com/health/ready
```

Основные журналы:

```text
storage/logs/laravel-YYYY-MM-DD.log
storage/logs/moderation-YYYY-MM-DD.log
storage/logs/worker-moderation.log
storage/logs/worker-orders.log
```

В журнал модерации записываются идентификатор товара, версия, провайдер, попытка, решение, риск и время ответа. API-ключ и содержимое карточки товара в журнал не записываются.

Если `/health/ready` возвращает HTTP 503:

```bash
sudo supervisorctl status
php artisan queue:failed
tail -n 100 storage/logs/worker-moderation.log
tail -n 100 storage/logs/worker-orders.log
tail -n 100 storage/logs/moderation-$(date +%Y-%m-%d).log
```

## 9. Redis

Установите Redis и запретите публичный доступ к порту `6379`. Redis должен быть доступен только приложению через localhost или закрытую сеть.

```bash
sudo apt update
sudo apt install redis-server
sudo systemctl enable --now redis-server
redis-cli ping
```

Ожидаемый ответ:

```text
PONG
```

Проект использует отдельные логические базы:

- DB 1 — кеш каталога;
- DB 2 — пользовательские сессии;
- DB 3 — фоновые очереди;
- DB 0 оставлена для общих Redis-операций.

После изменения `.env`:

```bash
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
sudo supervisorctl restart granat-moderation-worker:*
sudo supervisorctl restart granat-orders-worker:*
```

Не выполняйте `redis-cli FLUSHALL` на рабочем сервере: команда удалит кеш, сессии и задачи очереди. Для очистки только кеша приложения используйте:

```bash
php artisan cache:clear
```

Безопасный откат при проблеме с Redis:

```env
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

После отката очистите конфигурационный кеш и запустите worker с подключением `database`.

## 10. Meilisearch

Поиск может работать в двух режимах:

- `SCOUT_DRIVER=null` — SQL fallback, не требует отдельного сервиса;
- `SCOUT_DRIVER=meilisearch` — быстрый поиск с исправлением опечаток и ранжированием.

Пример запуска локально через Docker:

```bash
docker run --name granat-meilisearch -d \
  -p 127.0.0.1:7700:7700 \
  -e MEILI_MASTER_KEY="replace-with-long-secret" \
  -v granat_meili_data:/meili_data \
  getmeili/meilisearch:v1.16
```

Не используйте тег `latest` на рабочем сервере. Порт `7700` не должен быть доступен из интернета.

Настройки приложения:

```env
SCOUT_DRIVER=meilisearch
SCOUT_QUEUE=true
SCOUT_PREFIX=granat_
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=replace-with-long-secret
```

После первого запуска или изменения структуры поискового документа:

```bash
php artisan scout:sync-index-settings
php artisan scout:import "App\Models\Product"
```

Индекс содержит только опубликованные товары с актуальным одобрением модерации и действующим тарифом продавца. Категория и выбранный город фильтруются внутри поискового движка до пагинации.

Если Meilisearch временно недоступен, публичный поиск автоматически переключается на SQL fallback. Для полного ручного отключения:

```env
SCOUT_DRIVER=null
```
