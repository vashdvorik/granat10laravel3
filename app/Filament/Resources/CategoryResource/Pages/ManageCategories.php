<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\CategoryTreeService;
use App\Services\CategoryTreeSnapshot;
use App\Support\CategoryPicker;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;

/**
 * Управление категориями: дерево с раскрытием, поиск, добавление, перенос, порядок, перенос товаров и удаление.
 * Заменяет прежние страницы «Категории» (плоская таблица) и «Дерево категорий» (только просмотр).
 */
class ManageCategories extends Page
{
    protected static string $resource = CategoryResource::class;

    protected static string $view = 'filament.resources.category-resource.pages.manage-categories';

    #[Url(as: 'q', history: false)]
    public string $search = '';

    #[Url(as: 'problems', history: false)]
    public bool $onlyProblems = false;

    /** Нужная категория: дерево раскроется до неё (ссылка «Показать в дереве» из формы редактирования). */
    #[Url(history: false)]
    public ?int $focus = null;

    /** @var array<int, int> */
    public array $expanded = [];

    public function getTitle(): string|Htmlable
    {
        return 'Категории';
    }

    /** Заголовок «Категории» уже в названии страницы: цепочка «Категории → Категории» не нужна. */
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function mount(): void
    {
        if ($this->focus) {
            $this->expanded = array_values(array_unique(array_merge(
                $this->expanded,
                app(CategoryTreeSnapshot::class)->ancestorsWithSelf($this->focus),
            )));
        }
    }

    protected function getViewData(): array
    {
        return [
            'snapshot' => app(CategoryTreeSnapshot::class)->build($this->expanded, $this->search, $this->onlyProblems),
        ];
    }

    // ------------------------------------------------------------------ дерево

    public function toggle(int $id): void
    {
        $this->expanded = in_array($id, $this->expanded, true)
            ? array_values(array_diff($this->expanded, [$id]))
            : [...$this->expanded, $id];
    }

    public function collapseAll(): void
    {
        $this->expanded = [];
    }

    public function showAllCategories(): void
    {
        $this->search = '';
        $this->onlyProblems = false;
    }

    public function moveUp(int $id): void
    {
        $this->shift($id, up: true);
    }

    public function moveDown(int $id): void
    {
        $this->shift($id, up: false);
    }

    private function shift(int $id, bool $up): void
    {
        $category = Category::query()->find($id);

        if (! $category) {
            return;
        }

        try {
            $service = app(CategoryTreeService::class);
            $moved = $up ? $service->moveUp(auth()->user(), $category) : $service->moveDown(auth()->user(), $category);
        } catch (AuthorizationException) {
            Notification::make()->title('Недостаточно прав')->danger()->send();

            return;
        }

        if (! $moved) {
            Notification::make()
                ->title($up ? 'Выше уже некуда' : 'Ниже уже некуда')
                ->body('Категория стоит '.($up ? 'первой' : 'последней').' среди соседей.')
                ->warning()
                ->send();
        }
    }

    // ------------------------------------------------------------------ действия

    public function addCategoryAction(): Action
    {
        return Action::make('addCategory')
            ->modalHeading(function (): string {
                $parent = $this->mountedCategory('parent');

                return $parent ? 'Новая подкатегория в «'.$parent->name.'»' : 'Новая корневая категория';
            })
            ->modalSubmitActionLabel('Создать')
            ->modalWidth('lg')
            ->form(function (): array {
                $parent = $this->mountedCategory('parent');
                $products = $parent ? Product::query()->where('category_id', $parent->getKey())->count() : 0;

                return [
                    Forms\Components\Placeholder::make('transfer_notice')
                        ->hiddenLabel()
                        ->visible($products > 0)
                        ->content('В «'.($parent?->name).'» уже есть товары ('.$products.'). Товары хранятся только в категориях '
                            .'последнего уровня, поэтому все они переедут в новую подкатегорию.'),
                    Forms\Components\TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255)
                        ->autofocus(),
                    Forms\Components\TextInput::make('alias')
                        ->label('Адрес (alias)')
                        ->helperText('Можно оставить пустым: адрес создастся из названия.')
                        ->maxLength(255)
                        ->unique(table: Category::class, column: 'alias'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Показывать в каталоге')
                        ->default(true),
                ];
            })
            ->action(function (array $data, Action $action): void {
                $parent = $this->mountedCategory('parent');
                $before = $parent ? Product::query()->where('category_id', $parent->getKey())->count() : 0;

                try {
                    $category = app(CategoryTreeService::class)->create(auth()->user(), [
                        ...$data,
                        'parent_id' => $parent?->getKey(),
                    ]);
                } catch (ValidationException $exception) {
                    $this->fail($exception, 'Категория не создана', $action);
                }

                if ($parent) {
                    $this->expand($parent->getKey());
                }

                Notification::make()
                    ->title('Категория «'.$category->name.'» создана')
                    ->body($before > 0 ? 'Товары ('.$before.') перенесены из «'.$parent->name.'» в неё.' : null)
                    ->success()
                    ->send();
            });
    }

    public function moveCategoryAction(): Action
    {
        return Action::make('moveCategory')
            ->modalHeading(fn (): string => 'Переместить «'.$this->mountedCategory()?->name.'»')
            ->modalDescription('Категория переедет вместе со всеми подкатегориями и товарами.')
            ->modalSubmitActionLabel('Переместить')
            ->modalWidth('lg')
            ->form([
                Forms\Components\Select::make('parent_id')
                    ->label('Новая родительская категория')
                    ->helperText('Начните вводить название. Пусто — категория станет корневой.')
                    ->searchable()
                    ->options(fn (): array => CategoryPicker::parents('', $this->mountedCategory()))
                    ->getSearchResultsUsing(fn (string $search): array => CategoryPicker::parents($search, $this->mountedCategory()))
                    ->getOptionLabelUsing(fn ($value): ?string => CategoryPicker::path($value))
                    ->placeholder('— Корневой уровень —'),
            ])
            ->action(function (array $data, Action $action): void {
                $category = $this->mountedCategory();
                $parentId = filled($data['parent_id'] ?? null) ? (int) $data['parent_id'] : null;

                try {
                    app(CategoryTreeService::class)->move(auth()->user(), $category, $parentId);
                } catch (ValidationException $exception) {
                    $this->fail($exception, 'Категория не перемещена', $action);
                }

                if ($parentId) {
                    $this->expand($parentId);
                }

                Notification::make()
                    ->title('Категория перемещена')
                    ->body('«'.$category->name.'» → '.($parentId ? CategoryPicker::path($parentId) : 'корневой уровень'))
                    ->success()
                    ->send();
            });
    }

    public function transferProductsAction(): Action
    {
        return Action::make('transferProducts')
            ->modalHeading(fn (): string => 'Перенести товары из «'.$this->mountedCategory()?->name.'»')
            ->modalDescription(function (): string {
                $category = $this->mountedCategory();
                $count = $category ? Product::query()->where('category_id', $category->getKey())->count() : 0;

                return 'Будет перенесено товаров: '.$count.'. Модерация и публикация товаров не меняются.';
            })
            ->modalSubmitActionLabel('Перенести')
            ->modalWidth('lg')
            ->form([
                Forms\Components\Select::make('target')
                    ->label('Куда перенести')
                    ->helperText('Только категории последнего уровня (без подкатегорий). Начните вводить название.')
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => CategoryPicker::leaves($search, $this->mountedCategory()))
                    ->getOptionLabelUsing(fn ($value): ?string => CategoryPicker::path($value)),
            ])
            ->action(function (array $data, Action $action): void {
                $category = $this->mountedCategory();
                $target = Category::query()->find($data['target'] ?? null);

                if (! $target) {
                    return;
                }

                try {
                    $moved = app(CategoryTreeService::class)->transferProducts(auth()->user(), $category, $target);
                } catch (ValidationException $exception) {
                    $this->fail($exception, 'Товары не перенесены', $action);
                }

                Notification::make()
                    ->title($moved > 0 ? 'Товары перенесены: '.$moved : 'В категории не было товаров')
                    ->body($moved > 0 ? '«'.$category->name.'» → '.CategoryPicker::path($target->getKey()) : null)
                    ->success()
                    ->send();
            });
    }

    public function toggleActiveAction(): Action
    {
        return Action::make('toggleActive')
            ->requiresConfirmation()
            ->modalHeading(fn (): string => ($this->mountedCategory()?->is_active ? 'Скрыть «' : 'Показать «').$this->mountedCategory()?->name.'»?')
            ->modalDescription(function (): string {
                $category = $this->mountedCategory();

                if (! $category || ! $category->is_active) {
                    return 'Категория снова появится в меню и каталоге.';
                }

                $products = Product::query()
                    ->whereIn('category_id', Category::query()->descendantsAndSelf($category->getKey())->pluck('id'))
                    ->count();

                return 'Категория пропадёт из меню и каталога, её страница перестанет открываться.'
                    .($products > 0
                        ? ' Товары в ней ('.$products.') останутся на сайте: на главной, в поиске и по прямой ссылке.'
                        : '');
            })
            ->modalSubmitActionLabel('Подтвердить')
            ->action(function (): void {
                $category = $this->mountedCategory();

                if (! $category) {
                    return;
                }

                $active = ! $category->is_active;
                app(CategoryTreeService::class)->setActive(auth()->user(), $category, $active);

                Notification::make()
                    ->title($active ? 'Категория показана' : 'Категория скрыта')
                    ->success()
                    ->send();
            });
    }

    public function deleteCategoryAction(): Action
    {
        return Action::make('deleteCategory')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(fn (): string => 'Удалить «'.$this->mountedCategory()?->name.'»?')
            ->modalDescription(function (): string {
                $category = $this->mountedCategory();

                if ($category && $category->children()->exists()) {
                    return 'У категории есть подкатегории: удалить её нельзя. Сначала переместите или удалите подкатегории.';
                }

                return 'Категория будет удалена без возможности восстановления.';
            })
            ->modalSubmitActionLabel('Удалить')
            ->form(function (): array {
                $category = $this->mountedCategory();
                $products = $category ? Product::query()->where('category_id', $category->getKey())->count() : 0;

                if ($products === 0) {
                    return [];
                }

                return [
                    Forms\Components\Select::make('move_to')
                        ->label('В категории '.$products.' товаров. Куда их перенести?')
                        ->helperText('Только категории последнего уровня. Начните вводить название.')
                        ->required()
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => CategoryPicker::leaves($search, $category))
                        ->getOptionLabelUsing(fn ($value): ?string => CategoryPicker::path($value)),
                ];
            })
            ->action(function (array $data, Action $action): void {
                $category = $this->mountedCategory();

                if (! $category) {
                    return;
                }

                try {
                    app(CategoryTreeService::class)->delete(
                        auth()->user(),
                        $category,
                        filled($data['move_to'] ?? null) ? (int) $data['move_to'] : null,
                    );
                } catch (ValidationException $exception) {
                    $this->fail($exception, 'Категория не удалена', $action);
                }

                $this->expanded = array_values(array_diff($this->expanded, [$category->getKey()]));

                Notification::make()->title('Категория «'.$category->name.'» удалена')->success()->send();
            });
    }

    // ------------------------------------------------------------------ служебное

    /** Категория из аргументов открытого окна действия (`mountAction('move', { category: 5 })`). */
    private function mountedCategory(string $key = 'category'): ?Category
    {
        $arguments = Arr::last($this->mountedActionsArguments ?? []) ?? [];
        $id = $arguments[$key] ?? null;

        return filled($id) ? Category::query()->find($id) : null;
    }

    private function expand(int $id): void
    {
        if (! in_array($id, $this->expanded, true)) {
            $this->expanded[] = $id;
        }
    }

    /** Показывает причину отказа и оставляет окно открытым, чтобы администратор мог исправить ввод. */
    private function fail(ValidationException $exception, string $title, Action $action): never
    {
        Notification::make()
            ->title($title)
            ->body((string) collect($exception->errors())->flatten()->first())
            ->danger()
            ->persistent()
            ->send();

        $action->halt();
    }
}
