<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SellerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Показывает магазин (общая информация)
     */
    public function index()
    {
        $user = Auth::user();
        $seller = $user->seller;
        
        if (!$seller) {
            return redirect()->route('account.store.create')
                ->with('info', 'Создайте магазин для начала работы');
        }
        
        return view('backend.cabinet.store.index', compact('user', 'seller'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Seller $seller)
    {
        //
    }

    /**
     * Форма редактирования магазина (О магазине)
     */
    public function edit(Seller $seller)
    {
        if ($seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }
        
        $user = Auth::user();
        
        return view('backend.cabinet.store.about', compact('seller', 'user'));
    }

    /**
     * Обновление данных магазина
     */
    public function update(Request $request, Seller $seller)
    {
        if ($seller->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('sellers', 'name')->ignore($seller->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'town' => ['nullable', 'string', 'max:255'],
            'sellerplace' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'link' => ['nullable', 'url'],
            'link_map' => ['nullable', 'url'],
            'telegram' => ['nullable', 'string', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'viber' => ['nullable', 'string', 'max:255'],
        ]);

        $seller->update($data);

        return redirect()->route('account.store.index')
            ->with('success', 'Магазин обновлён');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Seller $seller)
    {
        //
    }
}