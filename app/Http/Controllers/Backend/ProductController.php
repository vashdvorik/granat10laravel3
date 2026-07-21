<?php

namespace App\Http\Controllers\Backend;

use App\Application\Products\CreateProduct;
use App\Application\Products\Queries\ListSellerProducts;
use App\Application\Products\UpdateProduct;
use App\Exceptions\ProductActivationException;
use App\Exceptions\ProductImageDeletionException;
use App\Exceptions\ProductModerationException;
use App\Exceptions\ProductPushTooSoonException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Image as ProductImage;
use App\Models\Product;
use App\Services\BackendProductService;
use App\Services\ProductActionService;
use App\Services\ProductLimitService;
use App\Services\ProductModerationIssuePresenter;
use App\Services\ProductModerationService;
use App\Services\SellerCabinetStateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Список товаров продавца
     */
    public function index(
        Request $request,
        ListSellerProducts $listSellerProducts,
        SellerCabinetStateService $sellerCabinetStateService
    )
    {
        $user = Auth::user();
        $seller = $user->seller;

        if (! $seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }

        $sellerCabinetState = $sellerCabinetStateService->make($seller);

        return view('backend.cabinet.store.products', array_merge(
            $listSellerProducts->execute(
                $seller,
                $request->filled('category') ? $request->integer('category') : null,
                $request->query()
            ),
            compact('seller', 'user', 'sellerCabinetState')
        ));
    }

    /**
     * Форма создания товара
     */
    public function create(
        BackendProductService $backendProductService,
        ProductLimitService $productLimitService
    )
    {
        $user = Auth::user();
        $seller = $user->seller;

        if (! $seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }

        // Только корневые категории для первого уровня (из кеша)
        $categories = $backendProductService->getRootCategories();
        $vipAvailability = $productLimitService->canActivateVipProduct($seller);

        return view('backend.cabinet.products.create', compact('categories', 'seller', 'vipAvailability'));
    }

    /**
     * Сохранение нового товара
     */
    public function store(
        StoreProductRequest $request,
        CreateProduct $createProduct
    ) {
        $user = Auth::user();
        $seller = $user->seller;

        if (! $seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }

        try {
            $result = $createProduct->execute(
                $seller,
                $user,
                $request->validated(),
                $request->file('images', [])
            );
        } catch (ProductModerationException $exception) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['name' => $exception->getMessage()]);
        }

        $request->session()->forget('selectedPath');

        return redirect()->route('account.store.products.index')
            ->with('success', $result->userMessage);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product, ProductModerationIssuePresenter $issuePresenter)
    {
        $this->authorize('view', $product);

        $product->load(['category', 'seller', 'moderationActions.actor']);
        $product->load(['images' => function ($query) {
            $query->orderByDesc('is_main');
        }]);

        $moderationIssues = $issuePresenter->forProduct($product);

        return view('backend.cabinet.products.show', compact('product', 'moderationIssues'));
    }

    /**
     * Форма редактирования товара
     */
    public function edit(
        Product $product,
        BackendProductService $backendProductService,
        ProductModerationIssuePresenter $issuePresenter,
        ProductLimitService $productLimitService
    ) {
        $this->authorize('update', $product);

        // Только корневые категории для первого уровня (из кеша)
        $categories = $backendProductService->getRootCategories();

        // Получаем цепочку категорий товара (для предзаполнения)
        $selectedPath = $backendProductService->getSelectedPath($product->category_id);
        $moderationIssues = $issuePresenter->forProduct($product);
        $vipAvailability = $productLimitService->canActivateVipProduct($product->seller, $product->id);

        return view('backend.cabinet.products.edit', compact(
            'product',
            'categories',
            'selectedPath',
            'moderationIssues',
            'vipAvailability'
        ));
    }

    /**
     * Обновление товара
     */
    public function update(
        UpdateProductRequest $request,
        Product $product,
        UpdateProduct $updateProduct
    ) {
        $this->authorize('update', $product);

        try {
            $result = $updateProduct->execute(
                $product,
                $request->user(),
                $request->validated(),
                $request->file('images', [])
            );
        } catch (ProductModerationException $exception) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['name' => $exception->getMessage()]);
        }

        $request->session()->forget('selectedPath');

        return redirect()->route('account.store.products.index')
            ->with('success', $result->userMessage);
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
            $product->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Товар "'.$product->name.'" опубликован',
                'fragments' => $this->renderProductCardFragments($product),
            ]);
        }

        return redirect()->back()->with('success', 'Товар "'.$product->name.'" опубликован');
    }

    /**
     * Деактивация товара
     */
    public function deactivate(Product $product, ProductActionService $productActionService)
    {
        $this->authorize('deactivate', $product);

        $productActionService->deactivate($product);

        if (request()->ajax()) {
            $product->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Товар "'.$product->name.'" снят с публикации',
                'fragments' => $this->renderProductCardFragments($product),
            ]);
        }

        return redirect()->back()->with('success', 'Товар "'.$product->name.'" снят с публикации');
    }

    /**
     * Удаление изображения товара
     */
    public function destroyImage(ProductImage $image, ProductActionService $productActionService)
    {
        $product = $image->product;

        $this->authorize('manageImages', $product);

        try {
            $updatedProduct = $productActionService->deleteImage($image);
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
                'message' => 'Изображение удалено',
                'current_version' => $updatedProduct->moderation_version,
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
                'message' => 'Главное изображение обновлено',
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
            $product->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Товар "'.$product->name.'" поднят в списке',
                'push_at' => $result['pushed_at']->format('d.m.Y в H:i'),
                'next_available_at' => $result['next_available_at']->toIso8601String(),
                'fragments' => $this->renderProductCardFragments($product),
            ]);
        }

        return redirect()->back()->with('success', 'Товар "'.$product->name.'" поднят в списке');
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

    public function refreshStatuses(Request $request)
    {
        $seller = Auth::user()->seller;

        if (! $seller) {
            return response()->json([
                'products' => [],
            ]);
        }

        $productIds = collect($request->input('product_ids', []))
            ->map(fn ($productId) => (int) $productId)
            ->filter()
            ->unique()
            ->values();

        $clientSignatures = collect($request->input('signatures', []))
            ->mapWithKeys(fn ($signature, $productId) => [(int) $productId => (string) $signature]);

        if ($productIds->isEmpty()) {
            return response()->json([
                'products' => [],
            ]);
        }

        $products = Product::query()
            ->with(['category', 'images', 'seller'])
            ->where('seller_id', $seller->id)
            ->whereIn('id', $productIds)
            ->get();

        $changedProducts = [];

        foreach ($products as $product) {
            $currentSignature = $product->cabinet_state_signature;

            if ($clientSignatures->get($product->id) === $currentSignature) {
                continue;
            }

            $changedProducts[] = [
                'id' => $product->id,
                'signature' => $currentSignature,
                'card_html' => view('backend.cabinet.store.partials.product-card', [
                    'product' => $product,
                    'productCabinetState' => $product->cabinet_state,
                ])->render(),
            ];
        }

        return response()->json([
            'products' => $changedProducts,
        ]);
    }

    public function submitForModeration(
        Product $product,
        ProductModerationService $productModerationService
    ) {
        $this->authorize('submitForModeration', $product);

        try {
            $productModerationService->submit($product, Auth::user());
        } catch (ProductModerationException $exception) {
            if (request()->ajax()) {
                return response()->json([
                    'error' => $exception->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $exception->getMessage());
        }

        if (request()->ajax()) {
            $product->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Товар отправлен на модерацию',
                'fragments' => $this->renderProductCardFragments($product),
            ]);
        }

        return redirect()->back()->with('success', 'Товар отправлен на модерацию');
    }

    private function renderProductCardFragments(Product $product): array
    {
        $product->loadMissing(['category', 'images', 'seller']);
        $productCabinetState = $product->cabinet_state;

        return [
            'card_html' => view('backend.cabinet.store.partials.product-card', [
                'product' => $product,
                'productCabinetState' => $productCabinetState,
            ])->render(),
            'badge_html' => view('backend.cabinet.store.partials.product-card-badge', [
                'product' => $product,
                'productCabinetState' => $productCabinetState,
            ])->render(),
            'body_html' => view('backend.cabinet.store.partials.product-card-body', [
                'product' => $product,
                'productCabinetState' => $productCabinetState,
            ])->render(),
            'menu_html' => view('components.product-moderation-actions', [
                'product' => $product,
                'compact' => true,
            ])->render(),
        ];
    }
}
