<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SellerController extends Controller
{
    /*
    Применяет middleware auth ко всем методам контроллера
    */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Показывыет только свой магазин текущего пользователя
     */
    public function index(): View
    {
        $seller = Seller::where('user_id', auth()->id())->first();
        
        if (!$seller) {
            return view('backend.sellers.no-seller');
        }

        return view('backend.sellers.index', compact('seller'));
    }

    /**
     * Показывает форму создания магазина (если его еще нет)
     */
    public function create(): View
    {
        if (Seller::where('user_id', auth()->id())->exists()) {
            return redirect()->route('backend.sellers.index');
        }

        return view('backend.sellers.create');
    }

    /**
     * Создает новый магазин для текущего пользователя с валидацией 
     * всех полей.
     */
    public function store(Request $request): RedirectResponse
    {
        if (Seller::where('user_id', auth()->id())->exists()) {
            return redirect()->route('backend.sellers.index');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'town' => 'nullable|string|max:255',
            'sellerplace' => 'nullable|string',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'alias' => 'nullable|string|max:255|unique:sellers',
            'region_id' => 'nullable|integer',
            'link' => 'nullable|url',
            'link_map' => 'nullable|url',
            'telegram' => 'nullable|string',
            'instagram' => 'nullable|string',
            'viber' => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();
        Seller::create($validated);

        return redirect()->route('backend.sellers.index')
            ->with('success', 'Магазин создан');
    }

    /**
     * show(Seller $seller)
     * Показывает детальную информацию о магазине (только владельцу)
     */
    public function show(Seller $seller): View
    {
        // Простая проверка - магазин принадлежит текущему пользователю
        if ($seller->user_id !== auth()->id()) {
            abort(403);
        }

        return view('backend.sellers.show', compact('seller'));
    }

    /**
     * edit(Seller $seller)
     * Показывает форму редактирования магазина (только владельцу)
     */
    public function edit(Seller $seller): View
    {
        if ($seller->user_id !== auth()->id()) {
            abort(403);
        }

        return view('backend.sellers.edit', compact('seller'));
    }

    /**
     * update(Request $request, Seller $seller)
     * Обновляет данные магазина (только владельцу)
     */
    public function update(Request $request, Seller $seller): RedirectResponse
    {
        if ($seller->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'town' => 'nullable|string|max:255',
            'sellerplace' => 'nullable|string',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'alias' => 'nullable|string|max:255|unique:sellers,alias,' . $seller->id,
            'region_id' => 'nullable|integer',
            'link' => 'nullable|url',
            'link_map' => 'nullable|url',
            'telegram' => 'nullable|string',
            'instagram' => 'nullable|string',
            'viber' => 'nullable|string',
        ]);

        $seller->update($validated);

        return redirect()->route('backend.sellers.index')
            ->with('success', 'Магазин обновлен');
    }

    /**
     * destroy(Seller $seller)
     *  Удаляет магазин (только владельцу)
     */
    public function destroy(Seller $seller): RedirectResponse
    {
        if ($seller->user_id !== auth()->id()) {
            abort(403);
        }

        $seller->delete();

        return redirect()->route('backend.sellers.index')
            ->with('success', 'Магазин удален');
    }
}