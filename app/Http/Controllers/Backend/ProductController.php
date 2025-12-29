<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Список товаров продавца
     */
    public function index()
    {
        $user = Auth::user();
        $seller = $user->seller;
        
        if (!$seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }
        
        $products = $seller->products()
            ->with(['category', 'images'])
            ->orderByDesc('created_at')
            ->paginate(20);
        
        return view('backend.cabinet.store.products', compact('products', 'seller', 'user'));
    }

    /**
     * Форма создания товара
     */
    public function create()
    {
        $user = Auth::user();
        $seller = $user->seller;
        
        if (!$seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }
        
        $categories = Category::whereNull('parent_id')
            ->with('children')
            ->where('is_active', true)
            ->orderBy('number')
            ->get();
        
        return view('backend.cabinet.products.create', compact('categories', 'seller'));
    }

    /**
     * Сохранение нового товара
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $seller = $user->seller;
        
        if (!$seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'lowprice' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'desc' => ['nullable', 'string'],
            'features1' => ['nullable', 'string', 'max:255'],
            'features2' => ['nullable', 'string', 'max:255'],
            'features3' => ['nullable', 'string', 'max:255'],
            'features4' => ['nullable', 'string', 'max:255'],
            'is_vip' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        // Генерация уникального alias
        $baseAlias = Str::slug($data['name']);
        $alias = $baseAlias ?: Str::lower(Str::random(6));
        
        $counter = 1;
        while (Product::where('alias', $alias)->exists()) {
            $alias = $baseAlias . '-' . $counter;
            $counter++;
        }

        $data['alias'] = $alias;
        $data['seller_id'] = $seller->id;
        $data['push_at'] = now();
        
        if (isset($data['lowprice']) && $data['lowprice'] > 0) {
            $data['sale_started_at'] = now();
        }

        Product::create($data);

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
        
        $product->load(['category', 'images', 'seller']);
        
        return view('backend.cabinet.products.show', compact('product'));
    }

    /**
     * Форма редактирования товара
     */
    public function edit(Product $product)
    {
        if ($product->seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }
        
        $categories = Category::whereNull('parent_id')
            ->with('children')
            ->where('is_active', true)
            ->orderBy('number')
            ->get();
        
        return view('backend.cabinet.products.edit', compact('product', 'categories'));
    }

    /**
     * Обновление товара
     */
    public function update(Request $request, Product $product)
    {
        if ($product->seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'lowprice' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'desc' => ['nullable', 'string'],
            'features1' => ['nullable', 'string', 'max:255'],
            'features2' => ['nullable', 'string', 'max:255'],
            'features3' => ['nullable', 'string', 'max:255'],
            'features4' => ['nullable', 'string', 'max:255'],
            'is_vip' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        // Обновляем sale_started_at если появилась скидка
        if (isset($data['lowprice']) && $data['lowprice'] > 0 && !$product->lowprice) {
            $data['sale_started_at'] = now();
        } elseif (empty($data['lowprice'])) {
            $data['sale_started_at'] = null;
        }

        $product->update($data);

        return redirect()->route('account.store.products.index')
            ->with('success', 'Товар обновлён');
    }

    /**
     * Удаление товара
     */
    public function destroy(Product $product)
    {
        if ($product->seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }
        
        $product->delete();
        
        return redirect()->route('account.store.products.index')
            ->with('success', 'Товар удалён');
    }
}