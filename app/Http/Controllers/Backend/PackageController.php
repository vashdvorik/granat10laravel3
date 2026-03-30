<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $balanceBefore = $user->balance;
        // Проверка, что магазин существует
        if (!$seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }
        // Проверяем владение магазином (защита от IDOR)
        if ($seller->user_id !== $user->id) {
            abort(403, 'Доступ запрещён');
        }

    try {
        DB::transaction(function () use ($user, $seller, $package) {
            
            // 🔒 ВАЖНО! Блокируем запись пользователя для обновления
            $user = $user->lockForUpdate()->find($user->id);
            
            // Проверка баланса (если пакет платный)
            if ($package->price > 0 && $user->balance < $package->price) {
                throw new \Exception('Недостаточно средств на балансе');
            }
            
            //  ПРОВЕРКА ЛИМИТОВ: количество активных товаров
            $activeProductsCount = $seller->getActiveProductsCount();
            $maxProducts = $package->active_products_limit ?? PHP_INT_MAX; // null = безлимит

            if ($maxProducts !== PHP_INT_MAX && $activeProductsCount > $maxProducts) {
                $excess = $activeProductsCount - $maxProducts;
                throw new \Exception("Тариф \"{$package->name}\" позволяет опубликовать не более {$maxProducts} товаров. У вас сейчас активно {$activeProductsCount} товаров. Пожалуйста, отключите {$excess} " . ($excess == 1 ? 'товар' : ($excess < 5 ? 'товара' : 'товаров')) . ", чтобы активировать этот пакет.");
            }

            //  ПРОВЕРКА ЛИМИТОВ: количество VIP товаров
            $vipProductsCount = $seller->getVipProductsCount();
            $maxVipProducts = $package->vip_products_limit ?? PHP_INT_MAX; // null = безлимит

            if ($maxVipProducts !== PHP_INT_MAX && $vipProductsCount > $maxVipProducts) {
                $excessVip = $vipProductsCount - $maxVipProducts;
                if ($maxVipProducts == 0) {
                    throw new \Exception("Тариф \"{$package->name}\" не поддерживает VIP товары. У вас сейчас {$vipProductsCount} VIP " . ($vipProductsCount == 1 ? 'товар' : ($vipProductsCount < 5 ? 'товара' : 'товаров')) . ". Пожалуйста, снимите VIP-статус со всех товаров, чтобы активировать этот пакет.");
                } else {
                    throw new \Exception("Тариф \"{$package->name}\" позволяет иметь не более {$maxVipProducts} VIP " . ($maxVipProducts == 1 ? 'товара' : 'товаров') . ". У вас сейчас {$vipProductsCount} VIP " . ($vipProductsCount == 1 ? 'товар' : ($vipProductsCount < 5 ? 'товара' : 'товаров')) . ". Пожалуйста, снимите VIP-статус с {$excessVip} " . ($excessVip == 1 ? 'товара' : ($excessVip < 5 ? 'товара' : 'товаров')) . ", чтобы активировать этот пакет.");
                }
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
        });
        
        // ✅ Success - возвращаем ПОСЛЕ успешной транзакции
        activity('packages')
            ->causedBy($user)
            ->performedOn($package)
            ->withProperties([
                'seller_id'      => $seller->id,
                'seller_name'    => $seller->name,
                'package_name'   => $package->name,
                'price'          => $package->price,
                'balance_before' => $balanceBefore,
                'balance_after'  => $user->fresh()->balance,
            ])
            ->log('Активирован тариф "' . $package->name . '"');

        return redirect()->route('account.packages.index')
            ->with('success', 'Тариф "' . $package->name . '" успешно активирован!');
            
        } catch (\Exception $e) {
            // ✅ Ошибка - ловим и показываем пользователю
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}