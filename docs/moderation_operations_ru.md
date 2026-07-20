# Эксплуатация модерации товаров

Актуализировано: 25.06.2026.

Бизнес-правила запрещённых и спорных товаров описаны отдельно в
`product_moderation_policy_ru.md`.

## Реализованный процесс

1. Продавец сохраняет черновик или отправляет товар на проверку через
   `save_action`.
2. `publication_requested` хранит намерение публикации отдельно от
   фактического `is_active`.
3. Локальный анализ проверяет обязательные и явно запрещённые данные.
4. `ModerateProductJob` выполняет текстовую ИИ-проверку.
5. При включённой проверке изображений запускается
   `ModerateProductImagesJob`.
6. Автоматическое одобрение допускается только политикой
   `ProductAutoApprovalPolicy`.
7. Спорные результаты передаются администратору.
8. Продавец получает безопасные замечания без технических данных провайдера.

## Пользовательские состояния

Технические статусы хранятся в `ProductModerationStatus`, а интерфейс строится
через:

- `ProductDisplayStateResolver`;
- `ProductViewStateFactory`;
- `ProductStateTransitionService`;
- `ProductPrimaryAction`.

Blade и Filament не должны самостоятельно собирать бизнес-условия из
`is_active`, `moderation_status` и версий.

## Настройка

Основная конфигурация находится в `config/moderation.php`. Секреты хранятся
только в `.env`.

Минимальные параметры:

```env
PRODUCT_MODERATION_AI_ENABLED=false
PRODUCT_MODERATION_AI_PROVIDER=fake
PRODUCT_MODERATION_AI_MODE=shadow
PRODUCT_MODERATION_QUEUE=moderation
```

Для текстового провайдера используются `AI_MODERATION_*`, для отдельной
проверки изображений — `AI_IMAGE_MODERATION_*`.

Настройки доступны администратору в Filament, но production-секреты должны
управляться средствами развёртывания. После изменения конфигурации:

```bash
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
```

## Очередь

Worker должен обрабатывать очередь модерации:

```bash
php artisan queue:work --queue=moderation,default --tries=3 --timeout=90
```

Для локальной разработки можно запустить все runtime-процессы одной командой:

```bash
php artisan granat:work
```

Подробности: `docs/local_runtime_ru.md`.

Для production используйте systemd или Supervisor. Scheduler записывает
heartbeat worker и запускает повтор отдельных отказавших операций.

Проверка:

```bash
php artisan queue:failed
php artisan moderation:retry-failed --limit=100
```

## Режимы

- `shadow` — сохраняет результат ИИ, но не меняет публикацию;
- `auto` — разрешает автоматическое одобрение только при выполнении всех
  ограничений риска и категории.

Переход на `auto` выполняется только после проверки shadow-данных и ручной
калибровки. Автоматическое окончательное отклонение не используется.

## Изображения

Отдельная image-модерация реализована, но по умолчанию выключена:

```env
AI_IMAGE_MODERATION_ENABLED=false
AI_IMAGE_MODERATION_PROVIDER=gemini
AI_IMAGE_MODERATION_QUEUE=moderation
AI_IMAGE_MODERATION_ENDPOINT_PATH=/models/{model}:generateContent
AI_IMAGE_MODERATION_AUTH_HEADER=x-goog-api-key
AI_IMAGE_MODERATION_AUTH_PREFIX=
AI_IMAGE_MODERATION_QUEUE=moderation
```

Перед включением нужны:

- реальный API-ключ;
- проверка доступности модели;
- тест на допустимых и проблемных изображениях;
- наблюдение за лимитами, временем ответа и стоимостью;
- проверка действий повтора в Filament.

## Наблюдение и отказоустойчивость

Проверяйте:

- `/health/ready`;
- heartbeat worker;
- failed jobs;
- зависшие `processing` runs;
- часовой и дневной бюджет запросов;
- долю `needs_review`, ошибок и ручных решений;
- совпадение `moderation_version` и `moderated_version`.

## Ключевые файлы

- `app/Services/ProductModerationService.php`
- `app/Jobs/ModerateProductJob.php`
- `app/Jobs/ModerateProductImagesJob.php`
- `app/Services/ProductViewStateFactory.php`
- `app/Services/ProductStateTransitionService.php`
- `app/Filament/Pages/ModerationSettings.php`
- `config/moderation.php`

## Проверка

```bash
php artisan test tests/Feature/ProductModerationServiceTest.php
php artisan test tests/Feature/ModerateProductJobTest.php
php artisan test tests/Feature/ModerateProductImagesJobTest.php
php artisan test tests/Feature/ProductImageModerationFlowTest.php
php artisan test tests/Feature/ProductViewStateTest.php
```

## Зашифрованные API-ключи

Ключи, введённые через Filament, сохраняются в `site_settings` с шифрованием Laravel Crypt. Значения ключей не записываются в журнал действий и не возвращаются в форму.

Для однократного переноса существующих ключей из `.env` выполните:

```bash
php artisan moderation:migrate-secrets
```

Legacy-переменные остаются fallback на период перехода. Общий legacy-ключ импортируется только для активного провайдера, чтобы не смешивать ключи Gemini, DeepSeek и OpenAI.

В Filament доступны независимые кнопки проверки подключения текста и изображений. Проверка подтверждает доступность endpoint, ключа и модели, не выводя тело ошибки провайдера.

## Локальный OCR и QR-коды

Локальный сканер изображений работает до платного AI-запроса и ищет телефоны, ссылки, мессенджеры и данные QR-кодов. Используются внешние утилиты:

- `tesseract` — распознавание текста;
- `zbarimg` — чтение QR- и штрихкодов.

Если утилиты не установлены, модерация продолжает работать через выбранный AI-провайдер. Управление выполняется переменными:

```dotenv
AI_IMAGE_LOCAL_SCAN_ENABLED=true
AI_IMAGE_LOCAL_SCAN_TIMEOUT=15
AI_IMAGE_TESSERACT_BINARY=tesseract
AI_IMAGE_TESSERACT_LANGUAGE=eng
AI_IMAGE_ZBAR_BINARY=zbarimg
```

Для распознавания русскоязычного текста после установки языковых данных можно задать `AI_IMAGE_TESSERACT_LANGUAGE=eng+rus`.
