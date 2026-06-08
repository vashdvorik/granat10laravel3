<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CategoryTreeService
{
    public function create(User $actor, array $data): Category
    {
        Gate::forUser($actor)->authorize('create', Category::class);

        return DB::transaction(function () use ($data): Category {
            $parent = $this->resolveParent($data['parent_id'] ?? null);
            $category = new Category($this->attributes($data));

            if ($parent) {
                $category->appendToNode($parent)->save();
            } else {
                $category->saveAsRoot();
            }

            return $category->refresh();
        });
    }

    public function update(User $actor, Category $category, array $data): Category
    {
        Gate::forUser($actor)->authorize('update', $category);

        return DB::transaction(function () use ($category, $data): Category {
            $lockedCategory = Category::query()
                ->lockForUpdate()
                ->findOrFail($category->getKey());
            $parent = $this->resolveParent($data['parent_id'] ?? null);

            $this->validateParent($lockedCategory, $parent);
            $lockedCategory->fill($this->attributes($data));

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

            return $lockedCategory->refresh();
        });
    }

    public function delete(User $actor, Category $category): void
    {
        Gate::forUser($actor)->authorize('delete', $category);

        DB::transaction(function () use ($category): void {
            $lockedCategory = Category::query()
                ->lockForUpdate()
                ->findOrFail($category->getKey());

            if ($lockedCategory->children()->exists()) {
                throw ValidationException::withMessages([
                    'category' => 'Нельзя удалить категорию с дочерними категориями. Сначала переместите или удалите дочерние категории.',
                ]);
            }

            if ($lockedCategory->products()->exists()) {
                throw ValidationException::withMessages([
                    'category' => 'Нельзя удалить категорию с товарами. Сначала перенесите товары в другую категорию.',
                ]);
            }

            $lockedCategory->delete();
        });
    }

    private function attributes(array $data): array
    {
        return Arr::only($data, [
            'name',
            'alias',
            'number',
            'desc',
            'icon',
            'icon_color',
            'bg_color',
            'is_active',
        ]);
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
    }
}
