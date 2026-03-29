<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;

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
        
        return view('backend.cabinet.store.edit', compact('seller', 'user'));
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
            'name'        => ['required', 'string', 'max:255', Rule::unique('sellers', 'name')->ignore($seller->id)],
            'phone'       => ['nullable', 'string', 'max:50'],
            'town'        => ['nullable', 'string', 'max:255'],
            'sellerplace' => ['nullable', 'string', 'max:255'],
            'address'     => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'link'        => ['nullable', 'url'],
            'link_map'    => ['nullable', 'url'],
            'telegram'    => ['nullable', 'string', 'max:255'],
            'instagram'   => ['nullable', 'string', 'max:255'],
            'viber'       => ['nullable', 'string', 'max:255'],
            'logo'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        // Обработка логотипа магазина
        if ($request->hasFile('logo')) {
            // Удаляем старое фото
            if ($seller->photo) {
                Storage::disk('public')->delete($seller->photo);
            }
            $filename = 'logo_' . Str::uuid() . '.webp';
            $path     = 'sellers/' . $seller->id;
            $image    = Image::make($request->file('logo'))
                ->orientate()
                ->fit(400, 400, function ($c) { $c->upsize(); })
                ->encode('webp', 85);
            Storage::disk('public')->put($path . '/' . $filename, (string) $image);
            $data['photo'] = $path . '/' . $filename;
        }
        unset($data['logo']);

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