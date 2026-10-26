<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Лента «Новинки» на главной и быстрые фильтры над ней.
 *
 * Лента всегда показывает один и тот же порядок (VIP-товары сначала, затем по дате поднятия и создания), учитывает
 * выбранный по адресу город и может быть сужена до одной корневой категории (со всеми её подкатегориями).
 * Быстрые фильтры — корневые категории, в которых сейчас есть хотя бы один видимый товар: пустой фильтр покупателю не нужен.
 */
class HomeFeedService
{
    public const LIMIT = 8;

    private const CHIPS_TTL_SECONDS = 600;

    public function __construct(private readonly CatalogCacheService $cache)
    {
    }

    /** @return Collection<int, Product> */
    public function latest(?Category $root = null): Collection
    {
        return Product::query()
            ->visible()
            ->forRegionalContext()
            ->when($root, fn ($query) => $query->whereIn(
                'products.category_id',
                Category::query()->whereBetween('_lft', [$root->_lft, $root->_rgt])->select('id')
            ))
            ->with(['images', 'seller'])
            ->orderByVipFirst()
            ->orderByDesc('push_at')
            ->orderByDesc('created_at')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * Корневые категории с видимыми товарами выбранного города (по порядку в каталоге) с картинкой и цветами:
     * [['id' => 1, 'alias' => '...', 'name' => '...', 'icon' => ..., 'bg_color' => ..., 'icon_color' => ...], ...].
     * Нужны и быстрым фильтрам над лентой новинок, и плиткам категорий на главной. Запоминается на 10 минут отдельно для каждого города.
     *
     * @return Collection<int, array{id: int, alias: string, name: string, icon: ?string, bg_color: ?string, icon_color: ?string}>
     */
    public function chips(): Collection
    {
        $chips = Cache::remember($this->cache->regionalKey('home:feed-chips'), self::CHIPS_TTL_SECONDS, function (): array {
            $categoryIds = Product::query()->visible()->forRegionalContext()->distinct()->pluck('products.category_id');

            if ($categoryIds->isEmpty()) {
                return [];
            }

            $lefts = Category::query()->whereIn('id', $categoryIds)->pluck('_lft');

            return Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('number')
                ->get(['id', 'alias', 'name', 'icon', 'bg_color', 'icon_color', '_lft', '_rgt'])
                ->filter(fn (Category $root) => $lefts->contains(fn ($left) => $left >= $root->_lft && $left <= $root->_rgt))
                ->map(fn (Category $root) => [
                    'id' => $root->id,
                    'alias' => (string) $root->alias,
                    'name' => (string) $root->name,
                    'icon' => $root->icon,
                    'bg_color' => $root->bg_color,
                    'icon_color' => $root->icon_color,
                ])
                ->values()
                ->all();
        });

        return collect($chips);
    }

    /** Корневая категория из быстрых фильтров по её alias; чужие и пустые категории не принимаются. */
    public function chipCategory(string $alias): ?Category
    {
        $chip = $this->chips()->firstWhere('alias', $alias);

        return $chip ? Category::query()->find($chip['id']) : null;
    }
}
