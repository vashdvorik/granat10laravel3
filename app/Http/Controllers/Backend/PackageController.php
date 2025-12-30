<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PackageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Показать все доступные тарифы
     */
    public function index()
    {
        $user = Auth::user();
        $seller = $user->seller;
        
        // Получаем все активные пакеты
        $packages = Package::where('is_active', true)
            ->orderBy('price')
            ->get();
        
        // Текущий активный пакет продавца
        $currentPackage = $seller ? $seller->activePackage() : null;
        
        return view('backend.cabinet.packages.index', compact('packages', 'currentPackage', 'seller', 'user'));
    }

    /**
     * Купить/активировать пакет
     */
    public function purchase(Request $request, Package $package)
    {
        $user = Auth::user();
        $seller = $user->seller;
        // Проверка, что магазин существует
        if (!$seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }
        // Проверяем владение магазином (защита от IDOR)
        if ($seller->user_id !== $user->id) {
            abort(403, 'Доступ запрещён');
        }

        
        // Проверка баланса (если пакет платный)
        if ($package->price > 0 && $user->balance < $package->price) {
            return redirect()->back()
                ->with('error', 'Недостаточно средств на балансе');
        }
        
        // Деактивируем текущий пакет
        $currentPackage = $seller->activePackage();
        if ($currentPackage) {
            $currentPackage->update(['is_active' => false]);
        }
        
        // Создаём новый активный пакет
        $endDate = $package->duration_days 
            ? now()->addDays($package->duration_days) 
            : null;
        
        $seller->sellerPackages()->create([
            'package_id' => $package->id,
            'start_date' => now(),
            'end_date' => $endDate,
            'is_active' => true,
        ]);
        
        // Списываем средства (если пакет платный)
        if ($package->price > 0) {
            $user->decrement('balance', $package->price);
        }
        
        return redirect()->route('account.packages.index')
            ->with('success', 'Тариф "' . $package->name . '" успешно активирован!');
    }
}