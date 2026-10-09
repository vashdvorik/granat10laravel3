<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Все изменения дерева категорий: создание, перенос, порядок, удаление и перенос товаров.
 *
 * Главное правило каталога: товары лежат только в категориях последнего уровня (без подкатегорий).
 * Сервис не даёт его нарушить: при добавлении подкатегории товары родителя переезжают в неё, а перенос категории
 * в ту, где есть свои товары, запрещён. Иначе у товара остаётся «осиротевшая» категория, и продавец не может
 * ни сохранить карточку, ни отправить её на проверку.
 */
class CategoryTreeService
{
    public function __construct(
        private readonly CatalogCacheService $cache,
    ) {}

    /**
     * Создаёт категорию. Если в родителе уже есть товары, они переезжают в новую подкатегорию.
     */
    public function create(User $actor, array $data): Category
    {
        Gate::forUser($actor)->authorize('create', Category::class);

        $transferred = [];

        $category = $this->withoutWarming(function () use ($actor, $data, &$transferred): Category {
            return DB::transaction(function () use ($actor, $data, &$transferred): Category {
                $parent = $this->resolveParent($data['parent_id'] ?? null);
                $attributes = $this->attributes($data);
                $attributes['alias'] = filled($attributes['alias'] ?? null)
                    ? trim((string) $attributes['alias'])
                    : $this->uniqueAlias((string) ($attributes['name'] ?? ''));
                $category = new Category($attributes);

                if ($parent) {
                    $category->appendToNode($parent)->save();
                } else {
                    $category->saveAsRoot();
                }

                $this->renumberSiblings($parent?->getKey());

                if ($parent && Product::query()->where('category_id', $parent->getKey())->exists()) {
                    $transferred = $this->moveProducts($parent->getKey(), $category->getKey());
                    $this->logTransfer($actor, $parent, $category, count($transferred));
                }

                return $category->refresh();
            });
        });

        $this->afterChange(
            affected: array_filter([$category->parent_id, $category->getKey()]),
            productIds: $transferred,
        );

        return $category;
    }

    public function update(User $actor, Category $category, array $data): Category
    {
        Gate::forUser($actor)->authorize('update', $category);

        $oldParentId = null;
        $nameChanged = false;

        $updated = $this->withoutWarming(function () use ($category, $data, &$oldParentId, &$nameChanged): Category {
            return DB::transaction(function () use ($category, $data, &$oldParentId, &$nameChanged): Category {
                $lockedCategory = Category::query()
                    ->lockForUpdate()
                    ->findOrFail($category->getKey());
                $oldParentId = $lockedCategory->parent_id;
                $parent = $this->resolveParent($data['parent_id'] ?? null);

                $this->validateParent($lockedCategory, $parent);
                $lockedCategory->fill($this->attributes($data));
                $nameChanged = $lockedCategory->isDirty('name');

                if ($parent === null) {
                    if ($lockedCategory->isRoot()) {
                        $lockedCategory->save();
                    } else {
                        $lockedCategory->makeRoot()->save();
                    }
                } elseif ($lockedCategory->parent_id === $parent->getKey()) {
                    $lockedCategory->save();
                } else {
                    $lockedCategory->appendToNode($parent)->save();
                }

                if ($oldParentId !== $lockedCategory->parent_id) {
                    $this->renumberSiblings($oldParentId);
                    $this->renumberSiblings($lockedCategory->parent_id);
                }

                return $lockedCategory->refresh();
            });
        });

        $this->afterChange(
            affected: array_filter([$oldParentId, $updated->parent_id, $updated->getKey()]),
            productIds: $nameChanged ? $this->productIdsInSubtree($updated) : [],
        );

        return $updated;
    }

    /** Переносит категорию (с подкатегориями) к другому родителю; null — в корень. Ставит её последней среди новых соседей. */
    public function move(User $actor, Category $category, ?int $parentId): Category
    {
        Gate::forUser($actor)->authorize('update', $category);

        $oldParentId = $category->parent_id;

        $moved = $this->withoutWarming(function () use ($category, $parentId): Category {
            return DB::transaction(function () use ($category, $parentId): Category {
                $lockedCategory = Category::query()->lockForUpdate()->findOrFail($category->getKey());
                $oldParentId = $lockedCategory->parent_id;
                $parent = $this->resolveParent($parentId);

                $this->validateParent($lockedCategory, $parent);

                if ($oldParentId === ($parent?->getKey())) {
                    return $lockedCategory;
                }

                if ($parent === null) {
                    $lockedCategory->makeRoot()->save();
                } else {
                    $lockedCategory->appendToNode($parent)->save();
                }

                $this->renumberSiblings($oldParentId);
                $this->renumberSiblings($parent?->getKey());

                return $lockedCategory->refresh();
            });
        });

        $this->afterChange(affected: array_filter([$oldParentId, $moved->parent_id, $moved->getKey()]));

        return $moved;
    }

    /** Поднимает категорию на одну позицию среди соседей. false — выше уже некуда. */
    public function moveUp(User $actor, Category $category): bool
    {
        return $this->shift($actor, $category, up: true);
    }

    public function moveDown(User $actor, Category $category): bool
    {
        return $this->shift($actor, $category, up: false);
    }

    /** Показывает или скрывает категорию в каталоге. */
    public function setActive(User $actor, Category $category, bool $active): Category
    {
        Gate::forUser($actor)->authorize('update', $category);

        $category->forceFill(['is_active' => $active])->save();
        $this->afterChange(affected: [$category->getKey()]);

        return $category->refresh();
    }

    /**
     * Переносит все товары одной категории в другую. Целевая категория должна быть последнего уровня.
     * Модерация не сбрасывается: это перестройка каталога администратором, а не правка карточки продавцом.
     *
     * @return int сколько товаров перенесено
     */
    public function transferProducts(User $actor, Category $from, Category $to): int
    {
        Gate::forUser($actor)->authorize('update', $from);

        $productIds = $this->withoutWarming(function () use ($actor, $from, $to): array {
            return DB::transaction(function () use ($actor, $from, $to): array {
                $source = Category::query()->lockForUpdate()->findOrFail($from->getKey());
                $target = Category::query()->lockForUpdate()->findOrFail($to->getKey());

                if ($source->is($target)) {
                    throw ValidationException::withMessages([
                        'target' => 'Выберите другую категорию: товары уже находятся в этой.',
                    ]);
                }

                $this->assertCanHoldProducts($target);

                $moved = $this->moveProducts($source->getKey(), $target->getKey());

                if ($moved !== []) {
                    $this->logTransfer($actor, $source, $target, count($moved));
                }

                return $moved;
            });
        });

        $this->afterChange(affected: [$from->getKey(), $to->getKey()], productIds: $productIds);

        return count($productIds);
    }

    /**
     * Удаляет пустую категорию. Если в ней есть товары, их можно перенести в другую категорию и удалить эту.
     */
    public function delete(User $actor, Category $category, ?int $moveProductsTo = null): void
    {
        Gate::forUser($actor)->authorize('delete', $category);

        $parentId = $category->parent_id;
        $productIds = [];
        $targetId = null;

        $this->withoutWarming(function () use ($actor, $category, $moveProductsTo, &$productIds, &$targetId, &$parentId): void {
            DB::transaction(function () use ($actor, $category, $moveProductsTo, &$productIds, &$targetId, &$parentId): void {
                $lockedCategory = Category::query()
                    ->lockForUpdate()
                    ->findOrFail($category->getKey());
                $parentId = $lockedCategory->parent_id;

                if ($lockedCategory->children()->exists()) {
                    throw ValidationException::withMessages([
                        'category' => 'Нельзя удалить категорию с подкатегориями. Сначала переместите или удалите подкатегории.',
                    ]);
                }

                if ($lockedCategory->products()->exists()) {
                    if ($moveProductsTo === null) {
                        throw ValidationException::withMessages([
                            'category' => 'В категории есть товары. Выберите, куда их перенести, или перенесите их заранее.',
                        ]);
                    }

                    $target = Category::query()->lockForUpdate()->findOrFail($moveProductsTo);

                    if ($target->is($lockedCategory)) {
                        throw ValidationException::withMessages([
                            'target' => 'Нельзя переносить товары в удаляемую категорию.',
                        ]);
                    }

                    $this->assertCanHoldProducts($target);
                    $productIds = $this->moveProducts($lockedCategory->getKey(), $target->getKey());
                    $targetId = $target->getKey();
                    $this->logTransfer($actor, $lockedCategory, $target, count($productIds));
                }

                activity('categories')
                    ->causedBy($actor)
                    ->withProperties([
                        'category_id' => $lockedCategory->getKey(),
                        'name' => $lockedCategory->name,
                        'parent_id' => $lockedCategory->parent_id,
                    ])
                    ->log('Категория «'.$lockedCategory->name.'» удалена');

                $lockedCategory->delete();
                $this->renumberSiblings($lockedCategory->parent_id);
            });
        });

        $this->afterChange(affected: array_filter([$parentId, $targetId]), productIds: $productIds);
    }

    /**
     * Категории, где товары лежат вместе с подкатегориями: у таких товаров «осиротевшая» категория.
     *
     * @return Collection<int, Category>  с атрибутом products_count
     */
    public function categoriesWithOrphanedProducts(): Collection
    {
        return Category::query()
            ->whereRaw('_rgt > _lft + 1')
            ->whereHas('products')
            ->withCount('products')
            ->defaultOrder()
            ->get();
    }

    // ------------------------------------------------------------------ внутреннее

    private function shift(User $actor, Category $category, bool $up): bool
    {
        Gate::forUser($actor)->authorize('update', $category);

        $parentId = $category->parent_id;

        $shifted = $this->withoutWarming(function () use ($category, $up, $parentId): bool {
            return DB::transaction(function () use ($category, $up, $parentId): bool {
                $locked = Category::query()->lockForUpdate()->findOrFail($category->getKey());
                $result = $up ? $locked->up() : $locked->down();

                if ($result) {
                    $this->renumberSiblings($parentId);
                }

                return (bool) $result;
            });
        });

        if ($shifted) {
            $this->afterChange(affected: array_filter([$parentId, $category->getKey()]));
        }

        return $shifted;
    }

    /** Перенумеровывает «Номер сортировки» соседей по их порядку в дереве, чтобы порядок везде был одинаковым. */
    private function renumberSiblings(?int $parentId): void
    {
        $siblings = Category::query()
            ->when(
                $parentId === null,
                fn ($query) => $query->whereNull('parent_id'),
                fn ($query) => $query->where('parent_id', $parentId),
            )
            ->defaultOrder()
            ->get(['id', 'number']);

        foreach ($siblings->values() as $index => $sibling) {
            if ((int) $sibling->number !== $index + 1) {
                Category::query()->whereKey($sibling->getKey())->update(['number' => $index + 1]);
            }
        }
    }

    /** @return array<int, int> идентификаторы перенесённых товаров */
    private function moveProducts(int $fromId, int $toId): array
    {
        $ids = Product::query()->where('category_id', $fromId)->pluck('id')->all();

        if ($ids !== []) {
            Product::query()->whereIn('id', $ids)->update(['category_id' => $toId]);
        }

        return $ids;
    }

    private function logTransfer(User $actor, Category $from, Category $to, int $count): void
    {
        activity('categories')
            ->causedBy($actor)
            ->performedOn($to)
            ->withProperties([
                'from_category_id' => $from->getKey(),
                'from_category' => $from->name,
                'to_category_id' => $to->getKey(),
                'to_category' => $to->name,
                'products' => $count,
            ])
            ->log('Товары ('.$count.') перенесены из «'.$from->name.'» в «'.$to->name.'»');
    }

    private function assertCanHoldProducts(Category $target): void
    {
        if ($target->children()->exists()) {
            throw ValidationException::withMessages([
                'target' => 'Товары можно хранить только в категории последнего уровня. '
                    .'У «'.$target->name.'» есть подкатегории: выберите одну из них.',
            ]);
        }
    }

    /** Кеши каталога и поисковый индекс после изменения; один раз, а не на каждую правку внутри операции. */
    private function afterChange(array $affected, array $productIds = []): void
    {
        foreach (array_unique($affected) as $id) {
            $category = Category::query()->find($id);

            if ($category) {
                $this->cache->forgetCategoryProductsCountWithAncestors($category);
            }
        }

        Category::clearCategoriesCache();

        if ($productIds !== []) {
            rescue(fn () => Product::query()->whereIn('id', $productIds)->searchable());
        }
    }

    /** @return array<int, int> */
    private function productIdsInSubtree(Category $category): array
    {
        $ids = Category::query()->descendantsAndSelf($category->getKey())->pluck('id');

        return Product::query()->whereIn('category_id', $ids)->pluck('id')->all();
    }

    /** Внутри операции кеш категорий не прогревается на каждую правку: прогрев запускается один раз в afterChange. */
    private function withoutWarming(callable $callback): mixed
    {
        $previous = Category::$suppressCacheWarming;
        Category::$suppressCacheWarming = true;

        try {
            return $callback();
        } finally {
            Category::$suppressCacheWarming = $previous;
        }
    }

    private function attributes(array $data): array
    {
        return Arr::only($data, [
            'name',
            'alias',
            'desc',
            'icon',
            'icon_color',
            'bg_color',
            'is_active',
        ]);
    }

    /** Адрес из названия: «Мышки и клавиатуры» → mishki-i-klaviatury; при совпадении добавляется -2, -3 ... */
    private function uniqueAlias(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $alias = $base;
        $suffix = 2;

        while (Category::query()->where('alias', $alias)->exists()) {
            $alias = $base.'-'.$suffix++;
        }

        return $alias;
    }

    private function resolveParent(mixed $parentId): ?Category
    {
        if (blank($parentId)) {
            return null;
        }

        return Category::query()
            ->lockForUpdate()
            ->findOrFail($parentId);
    }

    private function validateParent(Category $category, ?Category $parent): void
    {
        if ($parent === null) {
            return;
        }

        if ($category->is($parent)) {
            throw ValidationException::withMessages([
                'parent_id' => 'Категория не может быть родителем самой себе.',
            ]);
        }

        if ($parent->isDescendantOf($category)) {
            throw ValidationException::withMessages([
                'parent_id' => 'Категорию нельзя переместить внутрь собственного потомка.',
            ]);
        }

        // Родитель остаётся родителем и сам по себе, менять его не нужно
        if ($category->parent_id === $parent->getKey()) {
            return;
        }

        $productsInParent = Product::query()->where('category_id', $parent->getKey())->count();

        if ($productsInParent > 0) {
            throw ValidationException::withMessages([
                'parent_id' => 'В категории «'.$parent->name.'» есть товары ('.$productsInParent.'). '
                    .'Если положить в неё подкатегорию, товары останутся «осиротевшими». '
                    .'Сначала перенесите товары в другую категорию последнего уровня или создайте подкатегорию кнопкой «+» '
                    .'в дереве: товары переедут в неё сами.',
            ]);
        }
    }
}
