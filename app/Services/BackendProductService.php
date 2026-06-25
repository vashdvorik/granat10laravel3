<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BackendProductService
{
    public function getRootCategories(): Collection
    {
        return Category::getCachedRootCategories();
    }

    public function getSelectedPath(?int $categoryId): array
    {
        if (! $categoryId) {
            return [];
        }

        $category = Category::find($categoryId);

        if (! $category) {
            return [];
        }

        return $this->getCategoryPath($category);
    }

    public function rememberSelectedPath(?Category $category): void
    {
        if (! $category) {
            return;
        }

        session()->put('selectedPath', $this->getCategoryPath($category));
    }

    public function generateUniqueAlias(string $name): string
    {
        $baseAlias = Str::slug($name);
        $baseAlias = $baseAlias ?: Str::lower(Str::random(6));
        $alias = $baseAlias;
        $counter = 1;

        while (Product::where('alias', $alias)->exists()) {
            $alias = $baseAlias.'-'.$counter;
            $counter++;
        }

        return $alias;
    }

    public function getChildCategories(?int $parentId): Collection
    {
        $query = $parentId
            ? Category::where('parent_id', $parentId)
            : Category::whereNull('parent_id');

        $categories = $query->where('is_active', true)
            ->orderBy('number')
            ->get(['id', 'name', 'parent_id']);

        $categories->each(function (Category $category) {
            $category->has_children = Category::where('parent_id', $category->id)
                ->where('is_active', true)
                ->exists();
        });

        return $categories;
    }

    private function getCategoryPath(Category $category): array
    {
        $selectedPath = $category->ancestors->pluck('id')->toArray();
        $selectedPath[] = $category->id;

        return $selectedPath;
    }
}
