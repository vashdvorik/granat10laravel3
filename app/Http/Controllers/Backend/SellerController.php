<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSellerRequest;
use App\Models\Seller;
use App\Services\SellerProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $this->authorize('update', $seller);
        
        $user = Auth::user();
        $regions = \App\Models\Region::query()
            ->active()
            ->ordered()
            ->with(['settlements' => fn ($query) => $query->active()->ordered()])
            ->get();
        
        return view('backend.cabinet.store.edit', compact('seller', 'user', 'regions'));
    }

    /**
     * Обновление данных магазина
     */
    public function update(UpdateSellerRequest $request, Seller $seller, SellerProfileService $sellerProfileService)
    {
        $this->authorize('update', $seller);

        $sellerProfileService->updateProfile(
            $seller,
            $request->validated(),
            $request->file('logo')
        );

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
