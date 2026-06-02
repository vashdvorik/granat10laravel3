<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UploadImageRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ImageService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Контроллер управления продуктами продавца
 */
class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private ImageService $imageService
    ) {
        $this->middleware('auth');
    }

    /**
     * Список товаров продавца
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $seller = $user->seller;

        if (! $seller) {
            return redirect()
                ->route('account.store.create')
                ->with('error', __('errors.no_seller_account'));
        }

        // Получение продуктов через сервис
        $products = $this->productService->getSellerProducts($seller);

        // Получаем ID категорий товаров продавца для фильтра
        $sellerCategoryIds = $seller->products()->distinct()->pluck('category_id')->toArray();

        // Строим дерево категорий
        $allCategoryIds = $this->getAllCategoryIds($sellerCategoryIds);
        $categoryTree = Category::whereIn('id', $allCategoryIds)
            ->where('is_active', true)
            ->defaultOrder()
            ->get()
            ->toTree();

        return view('backend.cabinet.store.products', compact(
            'products',
            'seller',
            'user',
            'categoryTree',
            'sellerCategoryIds'
        ));
    }

    /**
     * Форма создания товара
     */
    public function create()
    {
        $seller = Auth::user()->seller;

        if (! $seller) {
            return redirect()
                ->route('account.store.create')
                ->with('error', __('errors.no_seller_account'));
        }

        // Проверяем возможность создания через Policy
        $this->authorize('create', Product::class);

        $categories = Category::getCachedRootCategories();

        return view('backend.cabinet.products.create', compact('categories', 'seller'));
    }

    /**
     * Сохранение нового товара
     */
    public function store(StoreProductRequest $request)
    {
        $seller = Auth::user()->seller;

        if (! $seller) {
            return redirect()
                ->route('account.store.create')
                ->with('error', __('errors.no_seller_account'));
        }

        try {
            // Проверяем лимиты пакета
            if ($request->boolean('is_active') && ! $this->productService->canActivateProduct($seller)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['is_active' => __('errors.product_limit_reached')]);
            }

            if ($request->boolean('is_vip') && ! $this->productService->canActivateVipProduct($seller)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['is_vip' => __('errors.vip_product_limit_reached')]);
            }

            // Создание продукта через сервис
            $product = $this->productService->createProduct($seller, $request->validated());

            session()->forget('selectedPath');

            return redirect()
                ->route('account.store.products.index')
                ->with('success', __('messages.product_created'));
        } catch (\DomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Просмотр товара
     */
    public function show(Product $product)
    {
        $this->authorize('update', $product);

        $product->load(['category', 'seller', 'images']);

        return view('backend.cabinet.products.show', compact('product'));
    }

    /**
     * Форма редактирования товара
     */
    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $categories = Category::getCachedRootCategories();

        // Получаем цепочку категорий товара для предзаполнения
        $selectedPath = $this->getCategoryPath($product->category_id);

        return view('backend.cabinet.products.edit', compact('product', 'categories', 'selectedPath'));
    }

    /**
     * Обновление товара
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        try {
            // Проверяем лимиты если активируем
            if ($request->boolean('is_active') && ! $product->is_active) {
                if (! $this->productService->canActivateProduct($product->seller)) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->withErrors(['is_active' => __('errors.product_limit_reached')]);
                }
            }

            // Обновление через сервис
            $product = $this->productService->updateProduct($product, $request->validated());

            session()->forget('selectedPath');

            return redirect()
                ->route('account.store.products.index')
                ->with('success', __('messages.product_updated'));
        } catch (\DomainException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Удаление товара
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        try {
            $this->productService->deleteProduct($product);

            return redirect()
                ->route('account.store.products.index')
                ->with('success', __('messages.product_deleted'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => __('errors.server_error')]);
        }
    }

    /**
     * Загрузка изображений (AJAX)
     */
    public function uploadImages(UploadImageRequest $request)
    {
        try {
            $uploadedImages = [];

            foreach ($request->file('images') as $file) {
                $image = $this->imageService->uploadImage($file);
                $uploadedImages[] = [
                    'id' => $image->id,
                    'thumb_url' => asset('storage/'.$image->thumb_path),
                    'url' => asset('storage/'.$image->original_path),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => __('messages.images_uploaded'),
                'images' => $uploadedImages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('errors.server_error'),
            ], 500);
        }
    }

    /**
     * Удаление изображения (AJAX)
     */
    public function deleteImage(Request $request)
    {
        $request->validate([
            'image_id' => ['required', 'integer', 'exists:images,id'],
        ]);

        try {
            $this->imageService->deleteImages([$request->image_id]);

            return response()->json([
                'success' => true,
                'message' => __('messages.image_deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('errors.server_error'),
            ], 500);
        }
    }

    /**
     * Получение всех ID категорий включая предков
     */
    private function getAllCategoryIds(array $categoryIds): array
    {
        $allIds = [];

        foreach ($categoryIds as $catId) {
            $category = Category::find($catId);
            if ($category) {
                $allIds[] = $catId;
                $ancestors = $category->ancestors()->pluck('id')->toArray();
                $allIds = array_merge($allIds, $ancestors);
            }
        }

        return array_unique($allIds);
    }

    /**
     * Получение пути категории (хлебные крошки)
     */
    private function getCategoryPath(?int $categoryId): array
    {
        if (! $categoryId) {
            return [];
        }

        $category = Category::find($categoryId);
        if (! $category) {
            return [];
        }

        $path = $category->ancestors->pluck('id')->toArray();
        $path[] = $category->id;

        return $path;
    }
}
