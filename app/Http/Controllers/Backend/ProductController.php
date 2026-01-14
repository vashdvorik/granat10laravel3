<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
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

        // ===================================================
        // ШАГ 1: СОЗДАЕМ ВАЛИДАТОР С ПРАВИЛАМИ И СООБЩЕНИЯМИ
        // ===================================================
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'lowprice' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'category_id' => ['required', 'exists:categories,id'],
            'desc' => ['nullable', 'string'],
            'features1' => ['nullable', 'string', 'max:255'],
            'features2' => ['nullable', 'string', 'max:255'],
            'features3' => ['nullable', 'string', 'max:255'],
            'features4' => ['nullable', 'string', 'max:255'],
            'is_vip' => ['boolean'],
            'is_active' => ['boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            // Кастомные сообщения
            'name.required' => 'Поле "Название товара" обязательно для заполнения',
            'name.max' => 'Название товара не может быть длиннее 255 символов',
            'price.required' => 'Укажите цену товара',
            'price.numeric' => 'Цена должна быть числом',
            'price.min' => 'Цена не может быть отрицательной',
            'category_id.required' => 'Выберите категорию товара',
            'category_id.exists' => 'Выбранная категория не существует',
            'lowprice.numeric' => 'Цена со скидкой должна быть числом',
            'lowprice.min' => 'Цена со скидкой не может быть отрицательной',
            'lowprice.lt' => 'Цена со скидкой должна быть меньше обычной цены',
            'images.max' => 'Можно загрузить максимум 5 изображений',
            'images.*.image' => 'Файл должен быть изображением',
            'images.*.mimes' => 'Поддерживаются только форматы: JPEG, PNG, JPG, WebP',
            'images.*.max' => 'Размер изображения не должен превышать 5 МБ',
        ]);

        // ===================================================
        // ШАГ 2: ПРОВЕРЯЕМ БАЗОВУЮ ВАЛИДАЦИЮ (формат полей)
        // ===================================================
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // ===================================================
        // ШАГ 3: ПОЛУЧАЕМ ВАЛИДИРОВАННЫЕ ДАННЫЕ
        // ===================================================
        $data = $validator->validated();

        // ===================================================
        // ШАГ 4: ПРОВЕРЯЕМ ЛИМИТЫ ПАКЕТА (бизнес-логика)
        // ===================================================
        $isActive = $request->input('is_active', false);
        $isVip = $request->input('is_vip', false);
        
        // Если товар неактивен, лимиты не проверяем
        if ($isActive) {
            // Проверяем лимит активных товаров
            $activeCheck = $seller->canActivateProduct();
            if (!$activeCheck['allowed']) {
                $validator->errors()->add('is_active', $activeCheck['message']);
            }
            
            // Если товар VIP, проверяем лимит VIP товаров
            if ($isVip) {
                $vipCheck = $seller->canActivateVipProduct();
                if (!$vipCheck['allowed']) {
                    $validator->errors()->add('is_vip', $vipCheck['message']);
                }
            }
            
            // Если есть ошибки, возвращаем назад
            if ($validator->errors()->any()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        // ===================================================
        // ШАГ 5: ГЕНЕРАЦИЯ УНИКАЛЬНОГО ALIAS
        // ===================================================
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
            foreach ($request->file('images') as $index => $file) {
                // Генерируем уникальное имя
                $filename = Str::uuid() . '.webp';
                $path = 'products/' . date('Y/m');
                
                // Читаем и обрабатываем изображение
                $image = Image::make($file)->orientate();
                
                // Сохраняем оригинал (большое изображение - макс. 1200px)
                $image->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->encode('webp', 85);
                
                $fullPath = $path . '/' . $filename;
                Storage::disk('public')->put($fullPath, (string) $image);
                
                // Создаем запись в БД
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $fullPath,
                    'is_main' => $index === 0, // Первое изображение - главное
                ]);
            }
        }

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

        // ===================================================
        // ШАГ 1: СОЗДАЕМ ВАЛИДАТОР С ПРАВИЛАМИ И СООБЩЕНИЯМИ
        // ===================================================
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'lowprice' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'category_id' => ['required', 'exists:categories,id'],
            'desc' => ['nullable', 'string'],
            'features1' => ['nullable', 'string', 'max:255'],
            'features2' => ['nullable', 'string', 'max:255'],
            'features3' => ['nullable', 'string', 'max:255'],
            'features4' => ['nullable', 'string', 'max:255'],
            'is_vip' => ['boolean'],
            'is_active' => ['boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            // Кастомные сообщения
            'name.required' => 'Поле "Название товара" обязательно для заполнения',
            'name.max' => 'Название товара не может быть длиннее 255 символов',
            'price.required' => 'Укажите цену товара',
            'price.numeric' => 'Цена должна быть числом',
            'price.min' => 'Цена не может быть отрицательной',
            'category_id.required' => 'Выберите категорию товара',
            'category_id.exists' => 'Выбранная категория не существует',
            'lowprice.numeric' => 'Цена со скидкой должна быть числом',
            'lowprice.min' => 'Цена со скидкой не может быть отрицательной',
            'lowprice.lt' => 'Цена со скидкой должна быть меньше обычной цены',
            'images.max' => 'Можно загрузить максимум 5 изображений',
            'images.*.image' => 'Файл должен быть изображением',
            'images.*.mimes' => 'Поддерживаются только форматы: JPEG, PNG, JPG, WebP',
            'images.*.max' => 'Размер изображения не должен превышать 5 МБ',
        ]);

        // ===================================================
        // ШАГ 2: ПРОВЕРЯЕМ БАЗОВУЮ ВАЛИДАЦИЮ
        // ===================================================
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        // ===================================================
        // ШАГ 3: ОБРАБАТЫВАЕМ ЧЕКБОКСЫ И ПРОВЕРЯЕМ ЛИМИТЫ
        // ===================================================

        // Обрабатываем радио-кнопки и чекбоксы (конвертируем в boolean)
        $data['is_active'] = $request->input('is_active', 0) == '1' ? true : false;
        $data['is_vip'] = $request->input('is_vip', 0) == '1' ? true : false;

        $isActive = $data['is_active'];
        $isVip = $data['is_vip'];

        // Если товар активен, проверяем лимиты (исключая текущий товар)
        if ($isActive) {
            // Проверяем лимит активных товаров
            $activeCheck = $product->seller->canActivateProduct($product->id);
            if (!$activeCheck['allowed']) {
                $validator->errors()->add('is_active', $activeCheck['message']);
            }
            
            // Если товар VIP, проверяем лимит VIP товаров
            if ($isVip) {
                $vipCheck = $product->seller->canActivateVipProduct($product->id);
                if (!$vipCheck['allowed']) {
                    $validator->errors()->add('is_vip', $vipCheck['message']);
                }
            }
            
            // Если есть ошибки, возвращаем назад
            if ($validator->errors()->any()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        // ===================================================
        // ШАГ 4: ОБРАБОТКА СКИДКИ
        // ===================================================
        // Если появилась новая скидка - ставим дату начала
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
            $existingCount = $product->images()->count();
            $newFilesCount = count($request->file('images'));
            
            // Проверяем лимит
            if ($existingCount + $newFilesCount > 5) {
                return redirect()->back()
                    ->with('error', 'Превышен лимит изображений. Можно загрузить ещё ' . (5 - $existingCount) . ' фото.')
                    ->withInput();
            }
            
            foreach ($request->file('images') as $index => $file) {
                // Генерируем уникальное имя
                $filename = Str::uuid() . '.webp';
                $path = 'products/' . date('Y/m');
                
                // Читаем и обрабатываем изображение
                $image = Image::make($file)->orientate();
                
                // Сохраняем оригинал (большое изображение - макс. 1200px)
                $image->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->encode('webp', 85);
                
                $fullPath = $path . '/' . $filename;
                Storage::disk('public')->put($fullPath, (string) $image);
                
                // Создаем запись в БД
                // Если нет главного изображения - делаем первое загружаемое главным
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $fullPath,
                    'is_main' => $existingCount === 0 && $index === 0,
                ]);
            }
        }

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
        
        // Удаляем все изображения из storage
        foreach ($product->images as $image) {
            if (Storage::disk('public')->exists($image->path)) {
                Storage::disk('public')->delete($image->path);
            }
        }
        
        $product->delete();
        
        return redirect()->route('account.store.products.index')
            ->with('success', 'Товар удалён');
    }

    /**
     * Активация товара
     */
    public function activate(Product $product)
    {
        if ($product->seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }

        // Проверяем лимит активных товаров
        $activeCheck = $product->seller->canActivateProduct($product->id);
        if (!$activeCheck['allowed']) {
            if (request()->ajax()) {
                return response()->json([
                    'error' => $activeCheck['message']
                ], 422);
            }
            return redirect()->back()->with('error', $activeCheck['message']);
        }

        // Если товар VIP, проверяем лимит VIP товаров
        if ($product->is_vip) {
            $vipCheck = $product->seller->canActivateVipProduct($product->id);
            if (!$vipCheck['allowed']) {
                if (request()->ajax()) {
                    return response()->json([
                        'error' => $vipCheck['message']
                    ], 422);
                }
                return redirect()->back()->with('error', $vipCheck['message']);
            }
        }

        $product->update(['is_active' => true]);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Товар "' . $product->name . '" активирован'
            ]);
        }

        return redirect()->back()->with('success', 'Товар "' . $product->name . '" активирован');
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
                'message' => 'Товар "' . $product->name . '" деактивирован'
            ]);
        }

        return redirect()->back()->with('success', 'Товар "' . $product->name . '" деактивирован');
    }

    /**
     * Удаление изображения товара
     */
    public function destroyImage(ProductImage $image)
    {
        $product = $image->product;
        
        if ($product->seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }

        // Удаляем файл из хранилища
        if (Storage::disk('public')->exists($image->path)) {
            Storage::disk('public')->delete($image->path);
        }

        // Если удаляем главное изображение и есть другие - делаем главным первое
        $wasMain = $image->is_main;
        $image->delete();

        if ($wasMain) {
            $firstImage = $product->images()->first();
            if ($firstImage) {
                $firstImage->update(['is_main' => true]);
            }
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
    public function setMainImage(ProductImage $image)
    {
        $product = $image->product;
        
        if ($product->seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }

        // Сбрасываем is_main у всех изображений товара
        $product->images()->update(['is_main' => false]);

        // Устанавливаем текущее как главное
        $image->update(['is_main' => true]);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Главное изображение обновлено'
            ]);
        }

        return redirect()->back()->with('success', 'Главное изображение обновлено');
    }
}