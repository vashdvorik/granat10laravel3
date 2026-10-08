<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests\DestroyShopRequest;
use App\Http\Requests\StoreShopRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Services\AccountSecurityService;
use App\Services\CabinetStatsService;
use App\Services\SellerCabinetStateService;
use App\Services\ShopService;

class CabinetController extends Controller
{
    /* 
    Отображает главную страницу личного кабинета 
    пользователя с информацией о магазине 
    */

    public function cabinet(SellerCabinetStateService $sellerCabinetStateService)
    {
        $user = Auth::user(); 
        $seller = $user->seller; // из модели user получили селлер

        if ($seller) {
            $this->authorize('view', $seller);
        }

        $sellerCabinetState = $sellerCabinetStateService->make($seller);

        return view('backend.cabinet.cabinet', compact('seller', 'sellerCabinetState'));
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

        $this->authorize('view', $seller);

        return view('backend.cabinet.stats', $cabinetStatsService->getStats($user, $seller));
    }
    public function createShop()
    {
        $user = Auth::user();
        if ($user->seller) {
            return redirect()->route('account.index')->with('error', 'Магазин уже создан');
        }
        $regions = \App\Models\Region::query()
            ->active()
            ->ordered()
            ->with(['settlements' => fn ($query) => $query->active()->ordered()])
            ->get();

        return view('backend.cabinet.create_shop', compact('regions'));
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
        $hadPassword = $user->hasPassword();

        try {
            $accountSecurityService->updatePassword(
                $user,
                $request->input('current_password'),
                $request->input('password')
            );
        } catch (\RuntimeException $e) {
            return redirect()->route('account.user.change_password')->with('error', $e->getMessage());
        }

        return redirect()->route('account.user.index')->with(
            'success',
            $hadPassword
                ? 'Пароль успешно изменён!'
                : 'Пароль задан. Теперь вы можете входить и по почте с паролем, и через Google.'
        );
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

        $this->authorize('delete', $user->seller);

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

        $this->authorize('delete', $user->seller);

        try {
            $sellerName = $accountSecurityService->deleteShop(
                $user,
                $request->input('password'),
                $request->input('confirmation')
            );
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        activity('sellers')
            ->causedBy($user)
            ->withProperties(['seller_name' => $sellerName])
            ->log('Удалён магазин "' . $sellerName . '"');

        return redirect()->route('account.index')->with('success', 'Магазин успешно удалён');
    }
}
