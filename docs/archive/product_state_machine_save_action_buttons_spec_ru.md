# Архив: спецификация state machine, `save_action` и кнопок товара

Дата: 15.06.2026.  
Статус: технический план этапа A, до реализации.

## 1. Цель этапа

Убрать из интерфейса продавца техническое управление `is_active` и сделать
каждое действие однозначным.

После этапа:

- состояние товара определяется в одном месте;
- форма передаёт намерение через `save_action`;
- Blade, контроллеры, policies и Filament используют одну матрицу переходов;
- продавец видит одно основное действие;
- недоступные действия не показываются;
- повторный запрос не создаёт двойной переход;
- техническая ошибка ИИ не становится пользовательским статусом.

## 2. Разделение понятий

### 2.1. Внутреннее состояние

В базе сохраняются отдельные признаки:

| Поле | Назначение |
|---|---|
| `moderation_status` | Технический этап проверки |
| `moderation_version` | Текущая версия содержимого |
| `moderated_version` | Версия, которую одобрили |
| `publication_requested` | Намерение опубликовать после одобрения |
| `is_active` | Фактическая публикация |
| активный тариф | Возможность публикации |

### 2.2. Пользовательское состояние

Продавцу не показываются внутренние комбинации. Сервис вычисляет
`ProductDisplayState`.

Предлагаемый enum:

```php
enum ProductDisplayState: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Checking = 'checking';
    case ManualReview = 'manual_review';
    case ChangesRequired = 'changes_required';
    case Rejected = 'rejected';
    case ApprovedHidden = 'approved_hidden';
    case Published = 'published';
    case Paused = 'paused';
    case Unavailable = 'unavailable';
}
```

`Failed` в пользовательский enum не входит.

## 3. Приоритет вычисления display state

Порядок обязателен. Более верхнее правило имеет приоритет.

| Приоритет | Условие | Display state |
|---:|---|---|
| 1 | `changes_required` | `changes_required` |
| 2 | `rejected` | `rejected` |
| 3 | `needs_review` или legacy `failed` | `manual_review` |
| 4 | `processing` | `checking` |
| 5 | `pending` | `queued` |
| 6 | `draft` | `draft` |
| 7 | актуально `approved`, но тариф/лимит/требования блокируют публикацию | `unavailable` |
| 8 | актуально `approved` и `is_active=true` | `published` |
| 9 | актуально `approved`, `is_active=false`, ранее был опубликован и продавец снял его | `paused` |
| 10 | актуально `approved`, `is_active=false` | `approved_hidden` |

Для надёжного различения `paused` и `approved_hidden` одного
`publication_requested` недостаточно:

- `publication_requested=false` после явного снятия — `paused`;
- `publication_requested=true`, но `is_active=false` — обычно
  `approved_hidden` или `unavailable`.

Если исторических данных недостаточно, на первом этапе допустимо объединить
`paused` и `approved_hidden` в «Не опубликован». Но архитектура должна позволять
разделить их без изменения Blade.

## 4. Инварианты состояния

Сервис переходов обязан поддерживать:

1. `is_active=true` допустим только при:
   - `moderation_status=approved`;
   - `moderated_version=moderation_version`;
   - выполненных публикационных требованиях;
   - активном тарифе.
2. `pending`, `processing`, `needs_review`, `changes_required`, `rejected`,
   `draft` всегда имеют `is_active=false` до появления revisions.
3. `moderated_version` заполняется только для одобренной версии.
4. Изменение модерируемого содержимого:
   - увеличивает `moderation_version`;
   - сбрасывает `moderated_version`;
   - переводит в `draft`;
   - до revisions снимает публикацию.
5. Изменение немодерируемого поля не меняет версию и одобрение.
6. Старый job не может применить решение к новой версии.
7. После ручного решения старый job не может изменить товар.
8. Техническая ошибка не означает нарушение.

## 5. Команды state machine

Не следует передавать произвольный новый статус. Используются именованные
команды:

```text
saveDraft
saveAndSubmit
submitCurrentDraft
publishApproved
unpublish
approve
requestChanges
reject
retryTechnicalCheck
moveToManualReview
invalidateModeration
pushUp
delete
appeal
```

Каждая команда:

- проверяет actor;
- блокирует строку товара;
- проверяет ожидаемую версию;
- выполняется транзакционно;
- записывает audit action;
- возвращает новый view state;
- безопасна при повторном одинаковом запросе.

## 6. Допустимые переходы

### 6.1. Команды продавца

| Из | Команда | В | Условия |
|---|---|---|---|
| `draft` | `submitCurrentDraft` | `pending` или `needs_review` | локальные правила, фото, тариф |
| `draft` | `saveDraft` | `draft` | валидные базовые поля |
| `changes_required` | `saveDraft` | `draft` | содержимое изменено |
| `changes_required` | `saveAndSubmit` | `pending`/`needs_review` | содержимое изменено и локальная проверка пройдена |
| `rejected` | `saveDraft` | `draft` | содержимое изменено, нет окончательного запрета |
| `rejected` | `saveAndSubmit` | `pending`/`needs_review` | содержимое изменено |
| `approved_hidden` | `publishApproved` | `published` | актуальное одобрение и публикационные требования |
| `paused` | `publishApproved` | `published` | те же условия |
| `published` | `unpublish` | `paused` | владелец |
| `published` | `pushUp` | `published` | cooldown |
| разрешённые состояния | `delete` | удалён | подтверждение |

### 6.2. Автоматические переходы

| Из | Событие | В |
|---|---|---|
| `pending` | worker начал обработку | `processing` |
| `processing` | безопасный allow + policy allow | `approved` + публикация по намерению |
| `processing` | review/block/high risk | `needs_review` |
| `processing` | retries исчерпаны | `needs_review` |
| `processing` | stale version | без изменения товара |
| `processing` | watchdog timeout | `needs_review` |

### 6.3. Команды администратора

| Из | Команда | В |
|---|---|---|
| `pending`/`needs_review` | `approve` | `approved` |
| `pending`/`needs_review` | `requestChanges` | `changes_required` |
| `pending`/`needs_review` | `reject` | `rejected` |
| техническая ручная очередь | `retryTechnicalCheck` | `pending` |
| зависший `processing` | `moveToManualReview` | `needs_review` |

Во время нормального `processing` ручные решения скрыты. После watchdog или
явного административного takeover задача становится stale и не может записать
результат.

## 7. Контракт `save_action`

### 7.1. Enum

```php
enum ProductSaveAction: string
{
    case Draft = 'save_draft';
    case Submit = 'save_and_submit';
    case Save = 'save';
}
```

### 7.2. Значение действий

#### `save_draft`

- сохранить данные;
- не отправлять ИИ;
- `publication_requested=false`;
- при изменении модерируемого содержимого перейти в `draft`;
- не использовать для опубликованного товара без явного предупреждения.

#### `save_and_submit`

- сохранить данные;
- `publication_requested=true`;
- при изменении содержимого создать новую moderation version;
- выполнить локальные проверки;
- отправить актуальную версию на модерацию;
- не выполнять отдельный повторный submit из контроллера.

#### `save`

- сохранить только немодерируемые изменения;
- не менять `publication_requested`;
- не менять `moderation_status`, версии и публикацию;
- доступно только когда сервер подтвердил, что изменились безопасные поля.

Сервер не должен доверять выбранному `save`: если изменилось модерируемое поле,
он требует `save_draft` или `save_and_submit`.

### 7.3. Поля формы

Удалить из seller request:

```text
is_active
```

Добавить:

```text
save_action required enum
expected_moderation_version required integer
idempotency_key required uuid
```

`publication_requested` и `is_active` никогда не принимаются напрямую из формы
продавца.

### 7.4. Значения по умолчанию

#### Создание

Основная кнопка отправляет `save_and_submit`.  
Вторичная — `save_draft`.

Не использовать скрытый radio с выбранным по умолчанию действием. Значение
передаёт нажатая submit-кнопка:

```html
<button name="save_action" value="save_draft">Сохранить черновик</button>
<button name="save_action" value="save_and_submit">Отправить на проверку</button>
```

#### Редактирование

- если изменились модерируемые поля — основная `save_and_submit`, вторичная
  `save_draft`;
- если изменились только безопасные поля — основная `save`;
- без JavaScript сервер всё равно корректно проверяет действие;
- JavaScript используется только для подсказки и изменения текста кнопки.

### 7.5. Результат команды

Application action возвращает DTO:

```php
final readonly class ProductWriteResult
{
    public function __construct(
        public Product $product,
        public ProductSaveAction $action,
        public bool $moderatedContentChanged,
        public bool $moderationDispatched,
        public ProductDisplayState $displayState,
        public string $userMessage,
    ) {}
}
```

Контроллер не вычисляет сообщение по `moderation_status`.

## 8. Классификация полей

### 8.1. Требуют повторной модерации

- `name`;
- `category_id`;
- `price`;
- `lowprice`;
- `desc`;
- `features1..4`;
- добавление изображения;
- замена изображения;
- удаление изображения.

### 8.2. Не требуют повторной модерации на первом этапе

- `can_order`;
- `delivery_enabled`, если не меняет публичный текст;
- `is_vip`;
- порядок уже проверенных изображений;
- назначение главного из уже проверенных изображений;
- `push_at`.

Список хранится в одном `ProductModeratedFields` class/config, а не дублируется
в сервисе и JavaScript.

## 9. Целевой `ProductViewState`

Расширить DTO:

```text
displayState
label
description
color
isPublic
requiresSellerAction
primaryAction
secondaryActions[]
canEdit
canDelete
canSubmit
canPublish
canUnpublish
canPushUp
canAppeal
canOpenPublicPage
disabledReason
submittedAt
decidedAt
```

`primaryAction` — enum, не строка:

```text
none
submit
edit
publish
push_up
resolve_unavailable
```

Blade не проверяет `moderation_status` и `is_active`.

## 10. Матрица кнопок продавца

### 10.1. Список товаров

Максимум две видимые кнопки и меню.

| Состояние | Кнопка 1 | Кнопка 2 | Меню |
|---|---|---|---|
| `draft` | Отправить на проверку | Изменить | Открыть, удалить |
| `queued` | — | Изменить скрыта по умолчанию | Открыть, удалить с предупреждением |
| `checking` | — | — | Открыть |
| `manual_review` | — | — | Открыть |
| `changes_required` | Исправить товар | — | Открыть, удалить |
| `rejected` | Исправить товар | — | Открыть, апелляция, удалить |
| `approved_hidden` | Опубликовать | Изменить | Открыть, удалить |
| `paused` | Опубликовать | Изменить | Открыть, удалить |
| `published` | Поднять | Изменить | Открыть на сайте, снять, удалить |
| `unavailable` | Устранить причину | Изменить | Открыть, удалить |

Редактирование `queued` допускается только если продуктово принято правило
«изменение отменяет текущую проверку». Предпочтительный MVP: разрешить через
подтверждение:

> Проверка текущей версии будет отменена. После сохранения товар потребуется
> отправить повторно.

### 10.2. Детальная страница

В header:

1. статус;
2. описание;
3. основная кнопка;
4. «Изменить», если это не основная кнопка;
5. меню вторичных действий.

Не дублировать нижний ряд кнопок.

### 10.3. Форма

| Контекст | Левая кнопка | Правая основная кнопка |
|---|---|---|
| Новый товар | Сохранить черновик | Отправить на проверку |
| Черновик | Сохранить черновик | Отправить на проверку |
| Требуются изменения | Сохранить черновик | Исправить и отправить |
| Отклонён | Сохранить черновик | Исправить и отправить |
| Одобрен/опубликован, изменены контролируемые поля | Отмена | Сохранить и отправить |
| Изменены только безопасные поля | Отмена | Сохранить |

### 10.4. Поведение отправки

- кнопка меняется на «Сохраняем…»;
- все submit-кнопки блокируются;
- показывается spinner;
- повторный submit с тем же idempotency key возвращает прежний результат;
- при validation error блокировка снимается;
- фокус переходит к первой ошибке;
- на мобильном панель остаётся видимой.

## 11. Матрица кнопок администратора

| Техническое состояние | Одобрить | Изменения | Отклонить | Retry ИИ | Takeover |
|---|---:|---:|---:|---:|---:|
| `draft` | Нет | Нет | Нет | Нет | Нет |
| `pending` | Да | Да | Да | Нет | Нет |
| `processing`, SLA не истёк | Нет | Нет | Нет | Нет | Нет |
| `processing`, SLA истёк | Нет | Нет | Нет | Нет | Да |
| `needs_review` | Да | Да | Да | Только после tech failure | Нет |
| `changes_required` | Нет | Нет | Нет | Нет | Нет |
| `rejected` | Пересмотр отдельным действием | Нет | Нет | Нет | Нет |
| `approved` | Нет | Нет | Нет | Нет | Нет |

Подпись approve:

- `publication_requested=true` — «Одобрить и опубликовать»;
- `publication_requested=false` — «Одобрить без публикации».

## 12. Изменения архитектуры

### 12.1. Новые классы

```text
App\Enums\ProductDisplayState
App\Enums\ProductSaveAction
App\Enums\ProductPrimaryAction
App\Data\ProductWriteResult
App\Services\ProductDisplayStateResolver
App\Services\ProductModeratedFields
```

### 12.2. Изменяемые классы

- `ProductRequest` — `save_action`, version, idempotency;
- `ProductWriteService` — возвращает result DTO;
- `CreateProduct`, `UpdateProduct` — команды вместо сырого флага;
- `ProductStateTransitionService` — полная state machine;
- `ProductViewStateFactory` — только через resolver;
- `ProductPolicy` — те же методы переходов;
- `ProductController` — сообщения из result DTO;
- Blade-компоненты действий — только view state;
- Filament actions — те же transition methods.

### 12.3. Маршруты

Сохранить отдельные command routes:

```text
POST  /products/{product}/submit-moderation
PATCH /products/{product}/publish
PATCH /products/{product}/unpublish
POST  /products/{product}/push-up
```

Название route `activate/deactivate` следует постепенно заменить на
`publish/unpublish`, оставив временные aliases для совместимости.

## 13. Миграции

Для базового `save_action` новая колонка не обязательна.

Рекомендуемые дополнительные поля:

```text
products.last_published_at nullable timestamp
products.last_unpublished_at nullable timestamp
products.state_changed_at nullable timestamp
products.state_changed_by_user_id nullable foreign key
```

Они позволяют различать `paused` и `approved_hidden`, проводить аудит и
показывать даты.

Для idempotency создать таблицу command keys либо использовать отдельную
универсальную таблицу:

```text
product_command_requests
- user_id
- product_id nullable
- command
- idempotency_key
- result_product_id
- created_at
unique(user_id, command, idempotency_key)
```

## 14. Обратная совместимость

1. Сначала добавить enums/resolver без изменения базы.
2. Сравнить новый display state со старым в тестах.
3. Добавить `save_action`, временно принимать legacy `is_active`.
4. Обновить Blade.
5. Удалить legacy input из форм.
6. После периода совместимости удалить обработку `is_active`.
7. Route aliases удалить отдельным релизом.

Старые товары:

- актуально approved + active → `published`;
- актуально approved + inactive → `approved_hidden` или `paused`;
- failed → `manual_review`;
- остальные отображаются по status.

## 15. Ошибки и сообщения

Команды возвращают пользовательские ошибки:

| Код | Сообщение |
|---|---|
| `version_conflict` | Товар изменился в другой вкладке. Обновите страницу и проверьте изменения. |
| `already_submitted` | Эта версия товара уже отправлена на проверку. |
| `not_approved` | Сначала товар должен пройти проверку. |
| `publication_blocked` | Публикация сейчас недоступна: {причина}. |
| `moderation_cancelled` | Проверка предыдущей версии отменена после изменений. |
| `invalid_save_action` | Выберите, сохранить черновик или отправить товар на проверку. |

Не возвращать продавцу имя exception, provider или HTTP status.

## 16. Тестовый план

### 16.1. Unit

- все комбинации resolver;
- приоритет display states;
- moderated fields diff;
- primary action для каждого состояния;
- labels и descriptions.

### 16.2. Feature

- create `save_draft`;
- create `save_and_submit`;
- update безопасных полей через `save`;
- сервер запрещает `save` при модерируемом изменении;
- `publication_requested` не теряется;
- duplicate idempotency key;
- optimistic version conflict;
- submit текущего draft;
- publish/unpublish;
- queued edit отменяет старую версию;
- legacy failed отображается manual review.

### 16.3. UI contract

Для каждого display state проверять:

- текст статуса;
- наличие основной кнопки;
- отсутствие запрещённых кнопок;
- содержимое меню;
- одинаковую матрицу на list/show/mobile;
- правильное значение `save_action`;
- отсутствие input `is_active`.

### 16.4. Filament

- действия соответствуют transition service;
- approve имеет правильную подпись;
- stale record нельзя решить повторно;
- processing нельзя модерировать до takeover;
- публикация недоступна без актуального approval.

## 17. Порядок реализации

1. Добавить enum и resolver display state.
2. Расширить view-state DTO.
3. Зафиксировать transition service contract.
4. Добавить `ProductSaveAction`.
5. Изменить requests и application actions.
6. Переписать write service.
7. Добавить idempotency/version checks.
8. Обновить controller messages.
9. Переписать Blade action component.
10. Обновить формы create/edit.
11. Обновить Filament actions.
12. Добавить миграцию времён состояния при необходимости.
13. Запустить targeted tests.
14. Запустить полный quality pipeline.
15. Провести ручную проверку desktop/mobile.

## 18. Definition of Done этапа A

- в формах продавца отсутствует `is_active`;
- используется enum `ProductSaveAction`;
- все состояния вычисляет один resolver;
- все кнопки получают разрешения из одного view state;
- list и show показывают одинаковые действия;
- у продавца не более одной основной кнопки;
- `publication_requested` не теряется при редактировании;
- безопасное изменение не запускает модерацию;
- модерируемое изменение не может сохраниться через `save`;
- повторная отправка идемпотентна;
- конфликт вкладок обнаруживается;
- technical failed отображается как manual review;
- Filament использует тот же transition service;
- тесты покрывают каждое состояние и каждую команду;
- полный PHPUnit проходит.
