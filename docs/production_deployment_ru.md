# Деплой BIMM на production-сервер

Инструкция рассчитана на Linux-сервер с Nginx, PHP-FPM, MySQL/MariaDB,
Redis и Supervisor. Проект работает на Laravel 10.

> Важно: корень сайта Nginx должен указывать на `/var/www/granat/public`, а не
> на корень репозитория. В репозитории сейчас нет `.env.example` и
> `package-lock.json`, поэтому команды ниже учитывают это.

## 0. Что понадобится

- домен, направленный DNS-записями на сервер;
- Ubuntu 22.04/24.04 или совместимый Linux;
- PHP 8.2 с PHP-FPM;
- MySQL 8 или MariaDB;
- Composer 2;
- Redis 6+;
- Nginx, Supervisor и Certbot;
- Git-доступ к репозиторию;
- API-ключи ИИ-провайдера, если будет включена автоматическая модерация;
- Telegram-бот, если нужны уведомления продавцам.

Создайте отдельного пользователя для деплоя, например `deploy`. Не выполняйте
обычные команды приложения от `root`.

## 1. До первого деплоя

1. Направьте `A`/`AAAA` записи домена на сервер.
2. Подготовьте базу и пароль пользователя БД.
3. Подготовьте Redis с закрытым доступом только с localhost или внутренней сети.
4. Решите, какие функции включаются сразу:
   - `FILAMENT_PANEL_ENABLED` — административная панель;
   - `REGIONALITY_ENABLED`, `REGIONAL_URLS_ENABLED`, `REGIONAL_SEO_ENABLED` — города и региональные URL;
   - `PRODUCT_MODERATION_AI_ENABLED` — текстовая ИИ-модерация;
   - `AI_IMAGE_MODERATION_ENABLED` — отдельная модерация изображений;
   - Telegram-уведомления.
5. Сделайте резервные копии существующей базы и `storage/app/public`, если это
   не новая установка.

Пример резервной копии:

```bash
sudo mkdir -p /var/backups/granat
sudo mysqldump --single-transaction --routines --triggers \
  -u granat_user -p granat \
  | gzip | sudo tee /var/backups/granat/database-$(date +%F-%H%M).sql.gz > /dev/null

sudo tar -czf /var/backups/granat/storage-$(date +%F-%H%M).tar.gz \
  -C /var/www/granat storage/app/public
```

Проверьте архивы:

```bash
sudo gzip -t /var/backups/granat/database-*.sql.gz
sudo tar -tzf /var/backups/granat/storage-*.tar.gz > /dev/null
```

## 2. Установка системных пакетов

Для PHP 8.2 на Ubuntu:

```bash
sudo apt update
sudo apt install -y \
  nginx mysql-server redis-server supervisor git unzip curl \
  php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
  php8.2-curl php8.2-gd php8.2-zip php8.2-bcmath php8.2-intl
```

Для локального сканера изображений дополнительно установите нужные бинарники:

```bash
sudo apt install -y tesseract-ocr zbar-tools
```

Проверьте версии:

```bash
php -v
php -m | grep -E 'bcmath|curl|fileinfo|gd|intl|mbstring|openssl|pdo_mysql|xml|zip'
composer --version
redis-cli ping
```

Ожидаемый ответ Redis — `PONG`.

Node.js не нужен: в проекте нет сборки фронтенда, стили и скрипты лежат в `public/`
в готовом виде.

## 3. База данных

Создайте отдельную БД и пользователя:

```bash
sudo mysql
```

```sql
CREATE DATABASE granat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'granat_user'@'localhost' IDENTIFIED BY 'CHANGE_THIS_LONG_PASSWORD';
GRANT ALL PRIVILEGES ON granat.* TO 'granat_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 4. Загрузка кода

```bash
sudo mkdir -p /var/www
sudo chown deploy:deploy /var/www

git clone <URL_РЕПОЗИТОРИЯ> /var/www/granat
cd /var/www/granat

composer install --no-dev --prefer-dist --optimize-autoloader
```

Создайте `.env` вручную:

```bash
cp /dev/null .env
chmod 600 .env
nano .env
```

Минимальная production-конфигурация:

```env
APP_NAME="BIMM"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://example.com
APP_LOCALE=ru

LOG_CHANNEL=daily
LOG_LEVEL=warning
MODERATION_LOG_LEVEL=info
MODERATION_LOG_DAYS=30

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=granat
DB_USERNAME=granat_user
DB_PASSWORD=CHANGE_THIS_LONG_PASSWORD

FILESYSTEM_DISK=public

CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_CONNECTION=session
SESSION_LIFETIME=120

QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2
REDIS_QUEUE_DB=3
REDIS_QUEUE_CONNECTION=queue
REDIS_QUEUE=default
REDIS_QUEUE_RETRY_AFTER=120
REDIS_QUEUE_BLOCK_FOR=5

FILAMENT_PANEL_ENABLED=true
FILAMENT_PANEL_PATH=admin

REGIONALITY_ENABLED=true
REGIONAL_URLS_ENABLED=true
REGIONAL_SEO_ENABLED=true
DESIGN_DEFAULT=legacy
CABINET_DESIGN_DEFAULT=legacy
VIP_PRICE_PER_DAY=1

PRODUCT_MODERATION_AI_ENABLED=false
PRODUCT_MODERATION_AI_PROVIDER=fake
PRODUCT_MODERATION_AI_MODE=shadow
PRODUCT_MODERATION_QUEUE=moderation
PRODUCT_MODERATION_TIMEOUT=60
PRODUCT_MODERATION_MAX_ATTEMPTS=3
PRODUCT_MODERATION_FAKE_DECISION=review

AI_IMAGE_MODERATION_ENABLED=false
AI_IMAGE_MODERATION_PROVIDER=gemini
AI_IMAGE_MODERATION_QUEUE=moderation
AI_IMAGE_MODERATION_TIMEOUT=60
AI_IMAGE_MODERATION_MAX_ATTEMPTS=3

TELEGRAM_TIMEOUT=10
ORDER_NOTIFICATION_QUEUE=orders
HEALTH_WORKER_MAX_AGE=180
ORDER_PERSONAL_DATA_RETENTION_DAYS=365
ORDER_ANONYMIZATION_BATCH_SIZE=200
```

`VIP_PRICE_PER_DAY` — стоимость одного дня VIP в рублях, пока администратор не
сохранил свою цену в разделе «Система → Настройки VIP». После первого сохранения
в админке действует цена из админки, а значение из `.env` больше не используется.

Сгенерируйте ключ приложения:

```bash
php artisan key:generate --force
```

Не копируйте `.env` в Git, резервные копии и чаты. После первого запуска
проверьте права:

```bash
chmod 600 .env
```

## 5. Настройка файлов и миграций

```bash
cd /var/www/granat
php artisan migrate --force
php artisan storage:link

mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views
sudo chown -R deploy:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

Не запускайте на рабочей базе:

```bash
php artisan migrate:fresh
php artisan db:seed
```

Для новой пустой базы после миграций загрузите справочники:

```bash
php artisan db:seed --class=CategoriesJsonSeeder --force
```

Тарифов (пакетов) в проекте больше нет, отдельный сидер для них не нужен.

> Миграции `2026_10_04_000001_add_vip_until_to_products_table` и
> `2026_10_04_000002_drop_package_system` добавляют срок VIP у товаров и
> **безвозвратно удаляют** таблицы `packages` и `seller_packages` и колонку
> `sellers.auto_renew`. Денежный баланс продавцов (`users.balance`) не меняется.
> Перед применением обязательно сделайте резервную копию базы (раздел 1).
> Порядок обновления существующего сайта с этой версией — в разделе 15.

## 6. Первый администратор

Временно добавьте в `.env`:

```env
ADMIN_NAME=Administrator
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=CHANGE_THIS_ADMIN_PASSWORD
```

Затем выполните:

```bash
php artisan config:clear
php artisan db:seed --class=AdminRoleSeeder --force
```

После этого удалите `ADMIN_PASSWORD` из `.env` и снова выполните:

```bash
php artisan config:clear
```

Адрес панели:

```text
https://example.com/admin/login
```

## 7. Nginx и HTTPS

Создайте `/etc/nginx/sites-available/granat`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name example.com www.example.com;

    root /var/www/granat/public;
    index index.php;
    client_max_body_size 12M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Активируйте сайт и проверьте конфигурацию:

```bash
sudo ln -s /etc/nginx/sites-available/granat /etc/nginx/sites-enabled/granat
sudo nginx -t
sudo systemctl reload nginx
```

После проверки DNS получите сертификат:

```bash
sudo certbot --nginx -d example.com -d www.example.com
sudo certbot renew --dry-run
```

После HTTPS проверьте, что в `.env` указан именно `https://example.com`.

## 8. Кеш Laravel

После заполнения `.env`:

```bash
php artisan optimize:clear
php artisan optimize
php artisan filament:optimize
```

После каждого изменения `.env` повторяйте эти команды.

## 9. Workers очереди

На production не используйте `php artisan granat:work`: эта команда предназначена
для локальной разработки и запускает несколько процессов в одном терминале.

Создайте Supervisor-конфигурации из шаблонов:

```bash
sudo cp deployment/supervisor/granat-moderation-worker.conf.example \
  /etc/supervisor/conf.d/granat-moderation-worker.conf
sudo cp deployment/supervisor/granat-orders-worker.conf.example \
  /etc/supervisor/conf.d/granat-orders-worker.conf
```

Проверьте в обоих файлах путь `/var/www/granat`, PHP и пользователя `www-data`.
Для Redis текущие команды должны быть такими:

```text
queue:work redis --queue=moderation --sleep=3 --tries=3 --timeout=90
queue:work redis --queue=orders --sleep=3 --tries=3 --timeout=30
```

Если Redis временно не используется, в командах замените `redis` на `database` и
установите `QUEUE_CONNECTION=database`. После этого обязательно должны быть
выполнены миграции таблиц `jobs` и `failed_jobs`.

Запустите workers:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start granat-moderation-worker:*
sudo supervisorctl start granat-orders-worker:*
sudo supervisorctl status
```

Проверка очередей:

```bash
php artisan queue:failed
tail -f storage/logs/worker-moderation.log
tail -f storage/logs/worker-orders.log
```

Правильный синтаксис запуска вручную:

```bash
php artisan queue:work redis --queue=moderation --tries=3 --timeout=90
```

Не пишите `databaseue=moderation`: это воспринимается Laravel как имя
подключения и вызывает ошибку `queue connection has not been configured`.

## 10. Планировщик Laravel

В `crontab` пользователя веб-сервера добавьте одну строку:

```bash
sudo crontab -u www-data -e
```

```cron
* * * * * cd /var/www/granat && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Планировщик выполняет:

- снятие VIP с товаров, у которых закончился оплаченный срок, каждые 5 минут (`products:expire-vip`);
- обезличивание старых завершённых и отменённых заказов в `02:00`;
- запись heartbeat worker каждую минуту, если очередь не `sync`.

Команды проверки автопродления тарифов (`packages:deactivate-expired`) больше нет:
тарифы удалены. Если в `crontab` или Supervisor осталась ссылка на неё, удалите её.

Если cron не работает, VIP не будет сниматься после окончания срока.

Проверить расписание и запустить снятие VIP вручную:

```bash
php artisan schedule:list
php artisan products:expire-vip
```

Проверка без изменения заказов:

```bash
php artisan orders:anonymize-expired --dry-run
```

## 11. Telegram-уведомления

Добавьте в `.env`:

```env
TELEGRAM_BOT_TOKEN=токен_от_BotFather
TELEGRAM_BOT_USERNAME=имя_бота_без_символа_@
TELEGRAM_WEBHOOK_SECRET=длинная_случайная_строка
```

Очередь заказов должна работать постоянно. После включения HTTPS зарегистрируйте
webhook:

```bash
curl -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/setWebhook" \
  -d "url=https://example.com/webhooks/telegram" \
  -d "secret_token=${TELEGRAM_WEBHOOK_SECRET}"
```

Проверка webhook:

```bash
curl "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/getWebhookInfo"
```

Продавец подключает Telegram из кабинета. Уведомление отправляется только после
проверки чата и при включённом `telegram_notifications_enabled`.

## 12. ИИ-модерация

Сначала разверните проект с безопасными настройками:

```env
PRODUCT_MODERATION_AI_ENABLED=false
AI_IMAGE_MODERATION_ENABLED=false
```

После проверки сайта откройте `/admin/moderation-settings` и настройте отдельно:

1. текстовую модерацию;
2. модерацию изображений;
3. провайдера, модель, базовый URL, endpoint и заголовок авторизации;
4. очередь, timeout и число попыток.

После включения провайдера убедитесь, что API-ключ сохранён, затем проверьте
тестовое подключение в панели. Для каждой включённой модерации worker должен
слушать соответствующую очередь. Ошибки смотрите здесь:

```bash
tail -f storage/logs/moderation-$(date +%Y-%m-%d).log
php artisan queue:failed
```

В production не включайте `PRODUCT_MODERATION_AI_MODE=auto`, пока не проверили
реальные ответы провайдера на тестовых товарах. При выключенной ИИ-модерации
новые товары должны переходить на ручную проверку согласно текущей логике проекта.

## 13. Meilisearch (необязательно)

Без отдельного сервиса проект использует SQL-поиск. Для Meilisearch настройте:

```env
SCOUT_DRIVER=meilisearch
SCOUT_QUEUE=true
SCOUT_PREFIX=granat_
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=длинный_секретный_ключ
```

Порт `7700` не открывайте в интернет. После установки:

```bash
php artisan scout:sync-index-settings
php artisan scout:import "App\\Models\\Product"
```

Если Meilisearch ещё не настроен, оставьте:

```env
SCOUT_DRIVER=null
```

## 13.1. Подтверждение почты и вход через Google

Кабинет открывается только после подтверждения почты, поэтому на сервере **обязательно** должна
работать отправка писем (`MAIL_*` в `.env`; письмо уходит сразу при регистрации). Проверка:

```bash
php artisan tinker --execute="Mail::raw('test', fn (\$m) => \$m->to('you@example.com')->subject('test'));"
```

В шапке письма используется `APP_NAME` — задайте название сайта вместо `Laravel`.

Вход одним кликом через Google включается переменными (без них кнопки не показываются):

```env
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
```

В Google Cloud Console (APIs & Services → Credentials → OAuth client ID, тип Web application) добавьте
Authorized redirect URI: `https://ВАШ-ДОМЕН/auth/google/callback`. После изменения `.env` выполните
`php artisan config:clear`.

Миграции этого релиза: `verify_existing_users_email` (помечает всех текущих пользователей подтверждёнными) и
`create_social_accounts_table`. В релизе добавлен пакет `laravel/socialite`: на сервере нужен обновлённый
`vendor/` (или `composer install --no-dev`).

### Защита регистрации

Регистрация и восстановление пароля защищены без внешних сервисов: скрытое поле-ловушка, метка времени
формы, лимиты по IP и запрет одноразовых почтовых доменов. Лимиты можно менять в `.env`
(`REGISTER_RATE_PER_MINUTE`, `REGISTER_RATE_PER_DAY`, `FORGOT_PASSWORD_RATE_PER_MINUTE`,
`FORGOT_PASSWORD_RATE_PER_DAY`). Если на сайт идёт волна фальшивых регистраций, включите бесплатную капчу
Cloudflare Turnstile (dash.cloudflare.com → Turnstile → Add site):

```env
TURNSTILE_SITE_KEY=...
TURNSTILE_SECRET_KEY=...
```

Без ключей капча не показывается. Если Cloudflare недоступен, формы продолжают работать. Лимиты по IP
хранятся в кеше (`CACHE_DRIVER`), поэтому на сервере должен работать нормальный кеш (Redis или file).

## 13.2. Релиз «Журнал баланса, VIP и раздел „Финансы“»

Что нужно сделать на сервере при обновлении:

```bash
cd /var/www/granat
php artisan migrate --force
php artisan optimize:clear
```

Новые миграции: `add_password_set_at_to_users_table` (существующим пользователям проставляет дату
регистрации, т. е. «пароль известен»), `create_balance_transactions_table` и `create_vip_purchases_table`.
Миграции повторяемы: если хостинг не дал создать внешний ключ, таблица всё равно создаётся, а причина
пишется в `storage/logs/laravel.log`.

Важно знать:

- В журнале баланса есть только операции, совершённые после обновления; прошлые остаются в журнале
  активности админки (`user_balance`). Баланс пользователей миграции не трогают.
- VIP, купленный до обновления, продолжает действовать до своей даты, но без записи о покупке: такой VIP
  продавец не может отключить досрочно (деньги за него вернуть автоматически нельзя), он закончится сам.
- В админке появился раздел «Финансы»: платежи, журнал баланса, покупки VIP и сводка на главной странице.
  Если пополнение картой «зависло», откройте «Финансы → Платежи» и нажмите «Сверить с банком».
- Планировщик (`schedule:run` каждую минуту в cron) должен работать: он снимает просроченный VIP, шлёт
  предупреждение за сутки и сверяет ожидающие платежи с банком.

## 14. Финальная проверка

```bash
cd /var/www/granat
php artisan about
php artisan route:list --path=admin
php artisan migrate:status
php artisan schedule:list
php artisan config:show queue
php artisan config:show moderation

curl -f https://example.com/health/live
curl -f https://example.com/health/ready
```

Проверьте вручную в браузере:

- главную страницу, каталог, поиск и страницу магазина;
- регистрацию и вход продавца;
- создание и редактирование товара;
- загрузку изображения;
- оформление заказа;
- отображение заказа в кабинете продавца;
- подключение Telegram;
- `/admin/login` и страницу модерации;
- `/admin/vip-settings` — стоимость одного дня VIP;
- страницу товара в кабинете продавца: блок «VIP-размещение» (сроки, цены, баланс);
- в кабинете и в админке нет разделов «Тарифы» и «Пакеты».

`/health/ready` может вернуть HTTP 503, если worker очереди не записал heartbeat.
В этом случае проверьте:

```bash
sudo supervisorctl status
sudo systemctl status nginx php8.2-fpm redis-server supervisor
tail -n 100 storage/logs/laravel-$(date +%Y-%m-%d).log
```

## 15. Обновление проекта

Перед обновлением сделайте backup и включите maintenance mode:

```bash
cd /var/www/granat
php artisan down --render="errors::503"
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan filament:optimize
php artisan queue:restart
sudo supervisorctl restart granat-moderation-worker:*
sudo supervisorctl restart granat-orders-worker:*
php artisan up
```

После обновления снова выполните раздел «Финальная проверка».

### Обновление до версии без тарифов (пакетов)

Эта версия удаляет систему тарифов и добавляет платный VIP за баланс продавца.
Выполняйте обновление с учётом следующего:

1. **Резервная копия базы обязательна.** Миграция `drop_package_system`
   безвозвратно удаляет таблицы `packages`, `seller_packages` и колонку
   `sellers.auto_renew`. Проверьте архив (`gzip -t`) до запуска миграций.
2. Остановите изменения на время обновления: `php artisan down`.
3. Выполните обычное обновление (`git pull`, `composer install`, миграции):

   ```bash
   git pull --ff-only
   composer install --no-dev --prefer-dist --optimize-autoloader
   php artisan migrate --force
   ```

   Миграции в этом релизе:

   - `2026_10_04_000001_add_vip_until_to_products_table` — колонка `products.vip_until`;
   - `2026_10_04_000002_drop_package_system` — удаление тарифов; старые записи журнала
     событий о тарифах сохраняются, но отвязываются от удалённых моделей.

4. Обязательно очистите кеш и перезапустите очереди (команды из блока выше:
   `optimize:clear`, `optimize`, `filament:optimize`, `queue:restart`, перезапуск Supervisor).
5. Проверьте, что cron вызывает `schedule:run` каждую минуту: от этого зависит
   автоматическое снятие VIP. Выполните `php artisan schedule:list` — в списке должна
   быть команда `products:expire-vip` (каждые 5 минут), а команды
   `packages:deactivate-expired` быть не должно.
6. Проверьте базу:

   ```bash
   php artisan tinker --execute="echo Schema::hasTable('packages') ? 'packages ЕСТЬ' : 'packages удалена'; echo PHP_EOL; echo Schema::hasColumn('products', 'vip_until') ? 'vip_until есть' : 'vip_until НЕТ';"
   ```

7. Откройте `/admin/vip-settings` и при необходимости измените стоимость одного дня VIP
   (по умолчанию 1 руб; сроки 1, 7, 30 и 365 дней считаются как цена дня × число дней).
8. Верните сайт в работу: `php artisan up`.

Что изменится для пользователей после обновления:

- ограничений по числу товаров больше нет, сайт и соцсети продавцов показываются всегда;
- товары продавцов с истёкшим тарифом снова станут видны, если они активны и прошли модерацию;
- товары, которые уже были VIP, остаются VIP без срока (их ведёт администратор);
- продавец покупает VIP сам на странице товара за деньги с баланса. Пополнение баланса
  пока выполняет администратор (корректировка баланса в админке).

## 16. Откат

Если релиз неработоспособен:

```bash
cd /var/www/granat
php artisan down
git log --oneline -5
git checkout <PREVIOUS_RELEASE_COMMIT>
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate:status
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
sudo supervisorctl restart granat-moderation-worker:*
sudo supervisorctl restart granat-orders-worker:*
php artisan up
```

Не откатывайте базу автоматически. Восстановление SQL-резервной копии выполняйте
только после проверки совместимости миграций и остановки записи в приложение.

**Откат релиза «без тарифов».** Предыдущая версия кода работает с таблицами
`packages` и `seller_packages`, а текущая миграция их удаляет. Откат через
`git checkout` без восстановления базы приведёт к ошибкам на сайте. Поэтому:

1. остановите сайт (`php artisan down`) и запись в приложение;
2. верните код предыдущего релиза (`git checkout <PREVIOUS_RELEASE_COMMIT>`,
   `composer install --no-dev --prefer-dist --optimize-autoloader`);
3. восстановите базу из резервной копии, сделанной перед обновлением
   (`gunzip -c database-<ДАТА>.sql.gz | mysql -u granat_user -p granat`).
   Изменения в базе, сделанные после обновления (заказы, покупки VIP, списания
   с баланса), будут потеряны — сверьтесь с логами до восстановления;
4. выполните `php artisan optimize:clear`, `php artisan optimize`,
   `php artisan queue:restart`, перезапустите Supervisor и `php artisan up`.

Команда `php artisan migrate:rollback --step=2` пересоздаёт только **пустые**
таблицы тарифов (данные тарифов она не вернёт) и удаляет `products.vip_until`
вместе со сроками купленных VIP. Для настоящего отката она не подходит.

## 17. Минимальный чек-лист перед передачей сайта

- [ ] `APP_DEBUG=false`, `.env` недоступен из web;
- [ ] Nginx указывает на `/public`;
- [ ] HTTPS и автопродление сертификата работают;
- [ ] миграции применены;
- [ ] `storage:link` создан;
- [ ] права на `storage` и `bootstrap/cache` проверены;
- [ ] Redis отвечает `PONG`;
- [ ] два worker-процесса Supervisor работают;
- [ ] cron вызывает `schedule:run` каждую минуту, в `schedule:list` есть `products:expire-vip`;
- [ ] в админке задана стоимость одного дня VIP (`/admin/vip-settings`);
- [ ] в кабинете и в админке нет разделов «Тарифы» и «Пакеты», таблицы `packages` и `seller_packages` удалены;
- [ ] `/health/live` и `/health/ready` отвечают успешно;
- [ ] создан администратор и удалён `ADMIN_PASSWORD`;
- [ ] Telegram webhook зарегистрирован, если он нужен;
- [ ] AI-модерация включена только после тестового подключения;
- [ ] сделан и проверен резервный backup базы и файлов.
