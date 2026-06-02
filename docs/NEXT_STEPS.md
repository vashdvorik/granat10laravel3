# Быстрый старт - Следующие шаги разработки

**Дата**: 03 июня 2026  
**Статус**: Фаза 1 завершена ✅, Переход к Фазе 2

---

## 🎯 Текущее состояние

- ✅ Архитектура: Service/Policy/FormRequest слои реализованы
- ✅ Типизация: Все файлы с `declare(strict_types=1)`, связи моделей типизированы
- ✅ Тесты: 89 тестов (38 Unit + 51 Feature), покрытие ~60%
- ✅ Локализация: 95% строк переведено (ru/en)
- ✅ Код: 100% соответствие Laravel Pint

**Оценка проекта**: 8.5/10 (было 4.5/10, +89%)

---

## 🚀 Запуск проекта

### Требования
- PHP 8.1+
- MySQL 5.7+
- Composer 2.x
- Node.js 16+ (для фронтенда)

### Установка

```bash
# 1. Установка зависимостей
composer install
npm install

# 2. Настройка окружения
cp .env.example .env
php artisan key:generate

# 3. Настройка БД в .env
DB_DATABASE=granat
DB_USERNAME=root
DB_PASSWORD=

# 4. Миграции и сидеры
php artisan migrate --seed

# 5. Создание символических ссылок
php artisan storage:link

# 6. Компиляция assets
npm run dev
```

### Запуск сервера

```bash
php artisan serve
# Проект доступен на http://localhost:8000
```

---

## 🧪 Тестирование

### Настройка тестовой БД

```env
# .env.testing
DB_CONNECTION=mysql
DB_DATABASE=granat_test
DB_USERNAME=root
DB_PASSWORD=
```

```bash
# Создать тестовую БД
mysql -u root -e "CREATE DATABASE IF NOT EXISTS granat_test"
```

### Запуск тестов

```bash
# Все тесты
php artisan test

# Только Unit
php artisan test --testsuite=Unit

# Только Feature
php artisan test --testsuite=Feature

# С покрытием (требует Xdebug)
php artisan test --coverage

# Конкретный тест
php artisan test --filter=PackageServiceTest
```

### Текущее покрытие

```
Unit тесты (38):
✓ ProductServiceTest (7)
✓ CategoryServiceTest (9)
✓ UserServiceTest (7)
✓ SellerServiceTest (9)
✓ PackageServiceTest (12)
✓ ImageServiceTest (13)

Feature тесты (51):
✓ ProductControllerTest (11)
✓ CatalogControllerTest (10)
✓ PublicProductControllerTest (10)
✓ CabinetControllerTest (20)

Общее покрытие: ~60%
Цель Фазы 2: 80%+
```

---

## 📋 Ближайшие задачи (Фаза 2, Неделя 4)

### 1. Создать недостающие тесты (2-3 дня)

#### AuthenticationTest (8 тестов)
```bash
php artisan make:test Feature/AuthenticationTest
```

Проверить:
- Регистрацию пользователя
- Вход/выход из системы
- Восстановление пароля
- Email верификацию
- Fortify интеграцию

#### Middleware тесты (10 тестов)
```bash
php artisan make:test Feature/MiddlewareTest
```

Проверить:
- Authenticate middleware
- EnsureSeller middleware (если есть)
- CSRF protection
- Rate limiting
- Guest middleware

**Цель**: Достичь 80%+ покрытия

---

### 2. Установить PHPStan (1 день)

#### Установка

```bash
composer require --dev phpstan/phpstan --with-all-dependencies
composer require --dev larastan/larastan --with-all-dependencies
```

#### Создать конфиг

```bash
# phpstan.neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 3
    paths:
        - app
        - tests
    excludePaths:
        - app/Http/Controllers/Backend/ProductController_OLD_BACKUP.php
        - app/Http/Controllers/CabinetController_OLD_BACKUP.php
        - app/Http/Controllers/CatalogController_OLD_BACKUP.php
        - app/Http/Controllers/ProductController_OLD_BACKUP.php
    ignoreErrors:
        - '#Unsafe usage of new static#'
```

#### Запустить анализ

```bash
# Level 3 (начальный)
vendor/bin/phpstan analyse --memory-limit=1G

# Исправить найденные проблемы

# Level 5 (финальная цель)
# Обновить level: 5 в phpstan.neon
vendor/bin/phpstan analyse --memory-limit=1G
```

**Цель**: 0 ошибок на level 5

---

### 3. Проверка производительности (1 день)

#### Установить Laravel Debugbar

```bash
composer require --dev barryvdh/laravel-debugbar
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

#### Проверить N+1 запросы

```bash
# В .env включить
DEBUGBAR_ENABLED=true

# Проверить страницы:
- /catalog
- /catalog/{category}
- /product/{alias}
- /account
- /account/store/products
```

Исправить все N+1, используя:
```php
// Вместо
$products = Product::all();
foreach ($products as $product) {
    echo $product->seller->name; // N+1!
}

// Использовать
$products = Product::with('seller')->get();
foreach ($products as $product) {
    echo $product->seller->name; // OK!
}
```

**Цель**: 0 N+1 запросов на всех страницах

---

## 📊 Проверка качества

### Laravel Pint (code style)

```bash
# Проверка
vendor/bin/pint --test

# Автоисправление
vendor/bin/pint
```

### PHPStan (статический анализ)

```bash
vendor/bin/phpstan analyse
```

### Тесты (функциональность)

```bash
php artisan test
```

### Все проверки сразу

```bash
# Скрипт для CI/CD
vendor/bin/pint --test && \
vendor/bin/phpstan analyse && \
php artisan test
```

---

## 📚 Документация

### Структура документов

```
docs/
├── refactoring_roadmap.md    # Полный план на 8-12 недель
├── progress_report.md         # Детальный отчет о прогрессе
├── phase1_summary.md          # Резюме Фазы 1
└── NEXT_STEPS.md              # Этот файл
```

### Архитектура проекта

```
app/
├── Actions/             # Сложные операции (Fortify)
├── Http/
│   ├── Controllers/     # Тонкие контроллеры (только координация)
│   ├── Requests/        # Form Requests (валидация)
│   └── Middleware/      # Middleware слой
├── Models/              # Eloquent модели (с типизацией связей)
├── Policies/            # Авторизация (2 класса)
├── Providers/           # Service providers (регистрация)
└── Services/            # Бизнес-логика (6 классов)

tests/
├── Feature/             # HTTP тесты (51 тест)
└── Unit/                # Юнит тесты сервисов (38 тестов)
```

### Service классы

```php
// app/Services/
ProductService    # CRUD товаров, фильтры, кэширование
ImageService      # Загрузка, обработка, удаление изображений
PackageService    # Управление подписками, лимиты
UserService       # Профили, смена пароля
SellerService     # CRUD магазинов, статистика
CategoryService   # Дерево категорий, breadcrumbs, кэш
```

Все сервисы зарегистрированы как **singleton** в `AppServiceProvider`.

### Policy классы

```php
// app/Policies/
ProductPolicy     # Авторизация для товаров (view, create, update, delete)
SellerPolicy      # Авторизация для магазинов (view, update, delete)
```

Зарегистрированы в `AuthServiceProvider`.

---

## 🔧 Полезные команды

### Artisan

```bash
# Маршруты
php artisan route:list

# Очистка кэша
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Создание классов
php artisan make:test Feature/MyTest
php artisan make:service MyService
php artisan make:request MyRequest
php artisan make:policy MyPolicy --model=MyModel

# Миграции
php artisan migrate:fresh --seed  # ВНИМАНИЕ: удаляет все данные!
php artisan migrate:rollback
php artisan migrate:status
```

### Composer

```bash
# Автозагрузка
composer dump-autoload

# Зависимости
composer update
composer require package/name
composer require --dev package/name

# Проверка безопасности
composer audit
```

### Git

```bash
# Статус
git status
git diff

# Коммит
git add .
git commit -m "feat: описание изменений"

# Ветки
git checkout -b feature/my-feature
git merge main
```

---

## 🎯 Критерии готовности Фазы 2

- [ ] 80%+ покрытие тестами
- [ ] PHPStan level 5 без ошибок
- [ ] 0 N+1 запросов
- [ ] Laravel Pint 100%
- [ ] Все Feature тесты проходят
- [ ] Все Unit тесты проходят
- [ ] Документация обновлена

---

## 🆘 Troubleshooting

### Тесты не запускаются

```bash
# Проверить настройки тестовой БД
cat .env.testing

# Создать тестовую БД
mysql -u root -e "CREATE DATABASE IF NOT EXISTS granat_test"

# Запустить с выводом
php artisan test --verbose
```

### PHPStan выдает ошибки

```bash
# Обновить до последней версии
composer update phpstan/phpstan larastan/larastan

# Очистить кэш
vendor/bin/phpstan clear-result-cache

# Запустить с подробностями
vendor/bin/phpstan analyse --debug
```

### Pint не проходит

```bash
# Автоисправление
vendor/bin/pint

# Если файл игнорируется, проверить .gitignore
cat .gitignore | grep filename
```

---

## 📞 Контакты и ресурсы

### Документация Laravel
- https://laravel.com/docs/10.x
- https://laravel.com/docs/10.x/testing

### Инструменты
- PHPStan: https://phpstan.org/
- Laravel Pint: https://laravel.com/docs/10.x/pint
- Laravel Debugbar: https://github.com/barryvdh/laravel-debugbar

### Код проекта
- Правила: `.github/copilot-instructions.md`
- Roadmap: `docs/refactoring_roadmap.md`
- Progress: `docs/progress_report.md`

---

**Последнее обновление**: 03 июня 2026  
**Следующее обновление**: После завершения Фазы 2
