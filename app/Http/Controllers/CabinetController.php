<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests\DestroyShopRequest;
use App\Http\Requests\StoreShopRequest;
use App\Http\Requests\ToggleAutoRenewRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Services\AccountSecurityService;
use App\Services\CabinetStatsService;
use App\Services\SellerSettingsService;
use App\Services\ShopService;

class CabinetController extends Controller
{
    /* 
    Отображает главную страницу личного кабинета 
    пользователя с информацией о магазине 
    */

    public function cabinet()
    {
        $user = Auth::user(); 
        $seller = $user->seller; // из модели user получили селлер
        return view('backend.cabinet.cabinet', compact('seller'));
    }

    /*
    Страница статистики: сводная информация по магазину продавца
    */
    public function stats(CabinetStatsService $cabinetStatsService)
    {
        $user = Auth::user();
        $seller = $user->seller;

        if (!$seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }

        return view('backend.cabinet.stats', $cabinetStatsService->getStats($user, $seller));
    }
    public function createShop()
    {
        $user = Auth::user();
        if ($user->seller) {
            return redirect()->route('account.index')->with('error', 'Магазин уже создан');
        }
        return view('backend.cabinet.create_shop');
    }
    /* 
    Сохраняет новый магазин в базе данных
    генерирует уникальный alias из имени магазина, валидирует данные. 
    */
    public function storeShop(StoreShopRequest $request, ShopService $shopService)
    {
        $user = Auth::user();
        if ($user->seller) {
            return redirect()->route('account.index')->with('error', 'Магазин уже создан');
        }

        $seller = $shopService->createShop(
            $user,
            $request->validated(),
            $request->file('logo')
        );

        activity('sellers')
            ->causedBy($user)
            ->performedOn($seller)
            ->withProperties(['seller_alias' => $seller->alias])
            ->log('Создан магазин "' . $seller->name . '"');

        return redirect()->route('account.index')->with('success', 'Магазин создан');
    }


    public function updatePassword(UpdatePasswordRequest $request, AccountSecurityService $accountSecurityService)
    {
        $user = Auth::user();

        try {
            $accountSecurityService->updatePassword(
                $user,
                $request->input('current_password'),
                $request->input('password')
            );
        } catch (\RuntimeException $e) {
            return redirect()->route('account.index')->with('error', $e->getMessage());
        }

        return redirect()->route('account.index')->with('success', 'Пароль успешно изменён!');
    }
    public function userIndex()
    {
        $user = Auth::user();
        return view('backend.cabinet.user.info', compact('user'));
    }

    // Показывает форму изменения пароля пользователя
    public function showChangePasswordForm()
    {
        return view('backend.cabinet.user.change_password');
    }
    /**
     * Показывает форму подтверждения удаления магазина
     */
    public function deleteShop()
    {
        $user = Auth::user();
        
        if (!$user->seller) {
            return redirect()->route('account.index')->with('error', 'У вас нет магазина');
        }
        
        return view('backend.cabinet.delete_shop', compact('user'));
    }

    /**
     * Удаляет магазин текущего пользователя
     */
    public function destroyShop(DestroyShopRequest $request, AccountSecurityService $accountSecurityService)
    {
        $user = Auth::user();

        if (!$user->seller) {
            return redirect()->route('account.index')->with('error', 'Магазин не найден');
        }

        try {
            $sellerName = $accountSecurityService->deleteShop($user, $request->input('password'));
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        activity('sellers')
            ->causedBy($user)
            ->withProperties(['seller_name' => $sellerName])
            ->log('Удалён магазин "' . $sellerName . '"');

        return redirect()->route('account.index')->with('success', 'Магазин успешно удалён');
    }
    public function toggleAutoRenew(ToggleAutoRenewRequest $request, SellerSettingsService $sellerSettingsService)
    {
        $seller = Auth::user()->seller;

        if (!$seller) {
            return response()->json(['error' => 'Магазин не найден'], 404);
        }

        $seller = $sellerSettingsService->updateAutoRenew($seller, $request->boolean('auto_renew'));

        return response()->json([
            'success' => true,
            'auto_renew' => $seller->auto_renew,
        ]);
    }
}
