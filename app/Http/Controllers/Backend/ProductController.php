<?php

namespace App\Http\Controllers\Backend;

use App\Exceptions\ProductActivationException;
use App\Exceptions\ProductImageDeletionException;
use App\Exceptions\ProductPushTooSoonException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\BackendProductService;
use App\Services\ProductActionService;
use App\Services\ProductWriteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Image as ProductImage;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Список товаров продавца
     */
    public function index(Request $request, BackendProductService $backendProductService)
    {
        $user = Auth::user();
        $seller = $user->seller;
        
        if (!$seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }
        return view('backend.cabinet.store.products', array_merge(
            $backendProductService->getIndexData($seller, $request),
            compact('seller', 'user')
        ));
        return view('backend.cabinet.store.products', compact('products', 'seller', 'user', 'categoryTree', 'sellerCategoryIds'));
    }

    /**
     * Форма создания товара
     */
    public function create(BackendProductService $backendProductService)
    {
        $user = Auth::user();
        $seller = $user->seller;
        
        if (!$seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }
        
        // Только корневые категории для первого уровня (из кеша)
        $categories = $backendProductService->getRootCategories();
        
        return view('backend.cabinet.products.create', compact('categories', 'seller'));
    }

    /**
     * Сохранение нового товара
     */
    public function store(
        StoreProductRequest $request,
        ProductWriteService $productWriteService
    ) {
        $user = Auth::user();
        $seller = $user->seller;
        
        if (!$seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }

        $productWriteService->create(
            $seller,
            $request->validated(),
            $request->file('images', [])
        );

        return redirect()->route('account.store.products.index')
            ->with('success', 'Товар создан');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $this->authorize('view', $product);
        
        $product->load(['category', 'seller']);
        $product->load(['images' => function ($query) {
            $query->orderByDesc('is_main');
        }]);
        
        return view('backend.cabinet.products.show', compact('product'));
    }

    /**
     * Форма редактирования товара
     */
    public function edit(Product $product, BackendProductService $backendProductService)
    {
        $this->authorize('update', $product);
        
        // Только корневые категории для первого уровня (из кеша)
        $categories = $backendProductService->getRootCategories();
        
        // Получаем цепочку категорий товара (для предзаполнения)
        $selectedPath = $backendProductService->getSelectedPath($product->category_id);
        
        return view('backend.cabinet.products.edit', compact('product', 'categories', 'selectedPath'));
    }

    /**
     * Обновление товара
     */
    public function update(
        UpdateProductRequest $request,
        Product $product,
        ProductWriteService $productWriteService
    ) {
        $this->authorize('update', $product);

        $productWriteService->update(
            $product,
            $request->validated(),
            $request->file('images', [])
        );

        return redirect()->route('account.store.products.index')
            ->with('success', 'Товар обновлён');
    }

    /**
     * Удаление товара
     */
    public function destroy(Product $product, ProductActionService $productActionService)
    {
        $this->authorize('delete', $product);

        $productActionService->delete($product);
        
        return redirect()->route('account.store.products.index')
            ->with('success', 'Товар удалён');
    }

    /**
     * Активация товара
     */
    public function activate(Product $product, ProductActionService $productActionService)
    {
        $this->authorize('activate', $product);

        try {
            $productActionService->activate($product);
        } catch (ProductActivationException $exception) {
            if (request()->ajax()) {
                return response()->json([
                    'error' => $exception->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $exception->getMessage());
        }

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Товар "' . $product->name . '" опубликован'
            ]);
        }

        return redirect()->back()->with('success', 'Товар "' . $product->name . '" опубликован');
    }

    /**
     * Деактивация товара
     */
    public function deactivate(Product $product, ProductActionService $productActionService)
    {
        $this->authorize('deactivate', $product);

        $productActionService->deactivate($product);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Товар "' . $product->name . '" снят с публикации'
            ]);
        }

        return redirect()->back()->with('success', 'Товар "' . $product->name . '" снят с публикации');
    }

    /**
     * Удаление изображения товара
     */
    public function destroyImage(ProductImage $image, ProductActionService $productActionService)
    {
        $product = $image->product;
        
        $this->authorize('manageImages', $product);

        try {
            $productActionService->deleteImage($image);
        } catch (ProductImageDeletionException $exception) {
            if (request()->ajax()) {
                return response()->json([
                    'error' => $exception->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $exception->getMessage());
        }

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Изображение удалено'
            ]);
        }

        return redirect()->back()->with('success', 'Изображение удалено');
    }

    /**
     * Установить главное изображение
     */
    public function setMainImage(ProductImage $image, ProductActionService $productActionService)
    {
        $product = $image->product;
        
        $this->authorize('manageImages', $product);

        $productActionService->setMainImage($image);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Главное изображение обновлено'
            ]);
        }

        return redirect()->back()->with('success', 'Главное изображение обновлено');
    }

    /**
     * Поднять товар выше в списке
     */
    public function pushUp(Product $product, ProductActionService $productActionService)
    {
        $this->authorize('pushUp', $product);

        try {
            $result = $productActionService->pushUp($product);
        } catch (ProductPushTooSoonException $exception) {
            if (request()->ajax()) {
                return response()->json([
                    'error' => $exception->getMessage(),
                    'available_at' => $exception->availableAt->toIso8601String(),
                ], 422);
            }

            return redirect()->back()->with('error', $exception->getMessage());
        }

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Товар "' . $product->name . '" поднят в списке',
                'push_at' => $result['pushed_at']->format('d.m.Y в H:i'),
                'next_available_at' => $result['next_available_at']->toIso8601String(),
            ]);
        }

        return redirect()->back()->with('success', 'Товар "' . $product->name . '" поднят в списке');
    }

    /**
     * Получить дочерние категории по ID родителя
     */
    public function getChildCategories(Request $request, BackendProductService $backendProductService)
    {
        return response()->json(
            $backendProductService->getChildCategories($request->integer('parent_id') ?: null)
        );
    }
}
