<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CategoryService;
use App\Services\ProductService;

/**
 * Контроллер просмотра товара (публичная часть)
 */
class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private CategoryService $categoryService
    ) {}

    /**
     * Страница товара
     */
    public function show(string $alias)
    {
        // Получаем товар
        $product = Product::visible()
            ->with(['images', 'seller', 'category'])
            ->where('alias', $alias)
            ->firstOrFail();

        // Проверяем доступ через Policy
        $this->authorize('view', $product);

        // Инкремент просмотров через сервис
        $this->productService->incrementViews($product);

        // Хлебные крошки категории
        $category = $product->category;
        $breadcrumbs = $category
            ? $this->categoryService->getBreadcrumbs($category)
            : collect();

        // Похожие товары
        $similarProducts = $this->productService->getSimilarProducts($product);

        // SEO данные
        $metaTitle = $product->meta_title ?: $product->name;
        $metaDescription = $product->meta_description ?: substr(strip_tags($product->description), 0, 160);

        return view('product', compact(
            'product',
            'breadcrumbs',
            'category',
            'similarProducts',
            'metaTitle',
            'metaDescription'
        ));
    }
}
