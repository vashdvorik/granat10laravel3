<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\Category;
use App\Services\BackendProductService;
use App\Services\ProductImageService;
use App\Services\ProductLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
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
        ProductImageService $productImageService,
        ProductLimitService $productLimitService,
        BackendProductService $backendProductService
    ) {
        $user = Auth::user();
        $seller = $user->seller;
        
        if (!$seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }

        // ===================================================
        // ШАГ 1: СОЗДАЕМ ВАЛИДАТОР С ПРАВИЛАМИ И СООБЩЕНИЯМИ
        // ===================================================
        $data = $request->validated();
        $validator = Validator::make([], []);

        // ===================================================
        // ШАГ 4: ПРОВЕРЯЕМ ЛИМИТЫ ПАКЕТА (бизнес-логика)
        // ===================================================
        $isActive = $request->input('is_active', false);
        $isVip = $request->input('is_vip', false);
        
        // ===================================================
        // ШАГ 3.5: ПРОВЕРЯЕМ, ЧТО КАТЕГОРИЯ КОНЕЧНАЯ (БЕЗ ДОЧЕРНИХ)
        // ===================================================
        $category = Category::find($data['category_id']);
        if ($category && !$category->isLeaf()) {
            // Сохраняем путь категории для восстановления
            $backendProductService->rememberSelectedPath($category);
            
            return redirect()->back()
                ->withErrors(['category_id' => 'Пожалуйста, выберите подкатегорию последнего уровня для размещения товара. Нельзя добавлять товары в категории, у которых есть подкатегории.'])
                ->withInput();
        }
        // Если товар неактивен, лимиты не проверяем
        $limitErrors = $productLimitService->validateForStore(
            $seller,
            (bool) $isActive,
            (bool) $isVip,
            $request->hasFile('images') && count($request->file('images')) > 0
        );

        foreach ($limitErrors as $field => $message) {
            $validator->errors()->add($field, $message);
        }

        if ($validator->errors()->any()) {
            $category = Category::find($data['category_id']);
            if ($category) {
                $backendProductService->rememberSelectedPath($category);
            }

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $data['alias'] = $backendProductService->generateUniqueAlias($data['name']);
        $data['seller_id'] = $seller->id;
        $data['push_at'] = now();
        
        // ===================================================
        // ШАГ 6: ОБРАБОТКА СКИДКИ
        // ===================================================
        if (isset($data['lowprice']) && $data['lowprice'] > 0) {
            $data['sale_started_at'] = now();
        }

        // ===================================================
        // ШАГ 7: СОЗДАНИЕ ТОВАРА
        // ===================================================
        $product = Product::create($data);

        // ШАГ 8: ЗАГРУЗКА И СЖАТИЕ ИЗОБРАЖЕНИЙ
        if ($request->hasFile('images')) {
            $productImageService->storeUploadedImages($product, $request->file('images'), true);
        }
        session()->forget('selectedPath');
        return redirect()->route('account.store.products.index')
            ->with('success', 'Товар создан');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        if ($product->seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }
        
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
        if ($product->seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }
        
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
        ProductImageService $productImageService,
        ProductLimitService $productLimitService,
        BackendProductService $backendProductService
    ) {
        if ($product->seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }

        // ===================================================
        // ШАГ 1: СОЗДАЕМ ВАЛИДАТОР С ПРАВИЛАМИ И СООБЩЕНИЯМИ
        // ===================================================
        $data = $request->validated();
        $validator = Validator::make([], []);

        // ===================================================
        // ШАГ 2.5: ПРОВЕРЯЕМ, ЧТО КАТЕГОРИЯ КОНЕЧНАЯ (БЕЗ ДОЧЕРНИХ)
        $category = Category::find($data['category_id']);
        if ($category && !$category->isLeaf()) {
            $backendProductService->rememberSelectedPath($category);

            return redirect()->back()
                ->withErrors(['category_id' => 'Р’С‹Р±РµСЂРёС‚Рµ РєРѕРЅРµС‡РЅСѓСЋ РєР°С‚РµРіРѕСЂРёСЋ...'])
                ->withInput();
        }

        // ===================================================
        // ШАГ 3: ОБРАБАТЫВАЕМ ЧЕКБОКСЫ И ПРОВЕРЯЕМ ЛИМИТЫ
        // ===================================================

        // Обрабатываем радио-кнопки и чекбоксы (конвертируем в boolean)
        $data['is_active'] = $request->input('is_active', 0) == '1' ? true : false;
        $data['is_vip'] = $request->input('is_vip', 0) == '1' ? true : false;

        $isActive = $data['is_active'];
        $isVip = $data['is_vip'];

        // Если товар активен, проверяем лимиты (исключая текущий товар)
        $limitErrors = $productLimitService->validateForUpdate(
            $product,
            $isActive,
            $isVip,
            $product->images()->exists() || ($request->hasFile('images') && count($request->file('images')) > 0)
        );

        foreach ($limitErrors as $field => $message) {
            $validator->errors()->add($field, $message);
        }

        if ($validator->errors()->any()) {
            $category = Category::find($data['category_id']);
            if ($category) {
                $backendProductService->rememberSelectedPath($category);
            }

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        if (isset($data['lowprice']) && $data['lowprice'] > 0 && !$product->lowprice) {
            $data['sale_started_at'] = now();
        } 
        // Если скидку убрали - очищаем дату
        elseif (empty($data['lowprice'])) {
            $data['sale_started_at'] = null;
        }

        // ===================================================
        // ШАГ 5: ОБНОВЛЕНИЕ ТОВАРА
        // ===================================================
        $product->update($data);

        // ===================================================
        // ШАГ 6: ЗАГРУЗКА НОВЫХ ИЗОБРАЖЕНИЙ
        // ===================================================
        if ($request->hasFile('images')) {
            $newFilesCount = count($request->file('images'));

            if (!$productImageService->canAddImages($product, $newFilesCount)) {
                return redirect()->back()
                    ->with('error', 'Превышен лимит изображений. Можно загрузить ещё ' . $productImageService->remainingImageSlots($product) . ' фото.')
                    ->withInput();
            }
            $productImageService->storeUploadedImages(
                $product,
                $request->file('images'),
                !$product->images()->where('is_main', true)->exists()
            );
        }
        session()->forget('selectedPath');
        return redirect()->route('account.store.products.index')
            ->with('success', 'Товар обновлён');
    }

    /**
     * Удаление товара
     */
    public function destroy(Product $product, ProductImageService $productImageService)
    {
        if ($product->seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }
        
        $productImageService->deleteProductFiles($product);
        $product->delete();
        
        return redirect()->route('account.store.products.index')
            ->with('success', 'Товар удалён');
    }

    /**
     * Активация товара
     */
    public function activate(Product $product, ProductLimitService $productLimitService)
    {
        if ($product->seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }

        $errorMessage = $productLimitService->activationError($product);
        if ($errorMessage !== null) {
            if (request()->ajax()) {
                return response()->json([
                    'error' => $errorMessage
                ], 422);
            }

            return redirect()->back()->with('error', $errorMessage);
        }
        $product->update(['is_active' => true]);

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
    public function deactivate(Product $product)
    {
        if ($product->seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }

        $product->update(['is_active' => false]);

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
    public function destroyImage(ProductImage $image, ProductImageService $productImageService)
    {
        $product = $image->product;
        
        if ($product->seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }

        // ✅ ПРОВЕРКА: Если товар активен и это последнее фото - запретить удаление
        if ($product->is_active && $product->images()->count() === 1) {
            $errorMessage = 'Нельзя удалить последнее изображение активного товара. Сначала деактивируйте товар или загрузите новое фото.';
            if (request()->ajax()) {
                return response()->json([
                    'error' => $errorMessage
                ], 422);
            }
            return redirect()->back()->with('error', $errorMessage);
        }

        $productImageService->deleteImage($image);

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
    public function setMainImage(ProductImage $image, ProductImageService $productImageService)
    {
        $product = $image->product;
        
        if ($product->seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }

        $productImageService->setMainImage($image);

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
    public function pushUp(Product $product)
    {
        if ($product->seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }

        // Проверяем, прошло ли 24 часа с последнего поднятия
        if ($product->push_at && $product->push_at->addHours(24)->isFuture()) {
            $availableAt = $product->push_at->addHours(24);
            $errorMessage = 'Поднять товар можно только раз в 24 часа. Следующее поднятие доступно: ' . $availableAt->format('d.m.Y в H:i');
            
            if (request()->ajax()) {
                return response()->json([
                    'error' => $errorMessage,
                    'available_at' => $availableAt->toIso8601String()
                ], 422);
            }
            return redirect()->back()->with('error', $errorMessage);
        }

        // Обновляем только push_at
        $product->update(['push_at' => now()]);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Товар "' . $product->name . '" поднят в списке',
                'push_at' => now()->format('d.m.Y в H:i'),
                'next_available_at' => now()->addHours(24)->toIso8601String()
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
