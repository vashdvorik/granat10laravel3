<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSellerRequest;
use App\Models\Seller;
use App\Services\SellerCabinetStateService;
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
        // Общая информация о магазине теперь на главной странице кабинета; адрес оставлен для старых ссылок.
        return redirect()->route('account.index');
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
    public function edit(Seller $seller, SellerCabinetStateService $sellerCabinetStateService)
    {
        $this->authorize('update', $seller);
        
        $user = Auth::user();
        $sellerCabinetState = $sellerCabinetStateService->make($seller);
        $regions = \App\Models\Region::query()
            ->active()
            ->ordered()
            ->with(['settlements' => fn ($query) => $query->active()->ordered()])
            ->get();
        
        return view('backend.cabinet.store.edit', compact('seller', 'user', 'regions', 'sellerCabinetState'));
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

        return redirect()->route('account.index')
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
