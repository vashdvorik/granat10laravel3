# Краткое развёртывание на production-сервере

## 1. Требования

- Linux, Nginx или Apache;
- PHP 8.1+ с расширениями: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `gd`;
- MySQL/MariaDB;
- Composer 2;
- Node.js 18+ и npm;
- корневая директория сайта должна указывать на `public`.

Перед развёртыванием сделайте резервную копию базы данных и `storage/app/public`.

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
QUEUE_CONNECTION=sync
FILAMENT_PANEL_ENABLED=true

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

Планировщик ежедневно проверяет истёкшие тарифы и автопродление.

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
```
