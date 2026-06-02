<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateSellerRequest;
use App\Services\PackageService;
use App\Services\SellerService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Контроллер личного кабинета пользователя
 */
class CabinetController extends Controller
{
    public function __construct(
        private UserService $userService,
        private SellerService $sellerService,
        private PackageService $packageService
    ) {
        $this->middleware('auth');
    }

    /**
     * Главная страница кабинета
     */
    public function cabinet()
    {
        $user = Auth::user();
        $seller = $user->seller;

        return view('backend.cabinet.cabinet', compact('seller'));
    }

    /**
     * Статистика магазина
     */
    public function stats()
    {
        $user = Auth::user();
        $seller = $user->seller;

        if (! $seller) {
            return redirect()
                ->route('account.store.create')
                ->with('error', __('errors.no_seller_account'));
        }

        // Получаем статистику через сервис
        $stats = $this->sellerService->getSellerStats($seller);

        // Информация о пакете
        $packageInfo = $this->packageService->getPackageUsageStats($seller);

        // История пакетов
        $packageHistory = $seller->sellerPackages()
            ->with('package')
            ->orderByDesc('id')
            ->get();

        return view('backend.cabinet.stats', compact(
            'seller',
            'stats',
            'packageInfo',
            'packageHistory'
        ));
    }

    /**
     * Форма создания магазина
     */
    public function createShop()
    {
        $user = Auth::user();

        if ($user->seller) {
            return redirect()
                ->route('account.index')
                ->with('error', __('errors.shop_already_exists'));
        }

        return view('backend.cabinet.create_shop');
    }

    /**
     * Сохранение нового магазина
     */
    public function storeShop(UpdateSellerRequest $request)
    {
        $user = Auth::user();

        if ($user->seller) {
            return redirect()
                ->route('account.index')
                ->with('error', __('errors.shop_already_exists'));
        }

        try {
            // Создание магазина через сервис
            $seller = $this->sellerService->createSeller($user, $request->validated());

            // Обработка логотипа
            if ($request->hasFile('logo')) {
                // TODO: вынести в ImageService
                $this->uploadSellerLogo($seller, $request->file('logo'));
            }

            // Активация базового пакета
            $basicPackage = \App\Models\Package::find(1); // Базовый пакет
            if ($basicPackage) {
                $this->packageService->activatePackage($seller, $basicPackage);
            }

            // Логирование
            activity('sellers')
                ->causedBy($user)
                ->performedOn($seller)
                ->withProperties(['seller_alias' => $seller->slug])
                ->log(__('messages.shop_created', ['name' => $seller->name]));

            return redirect()
                ->route('account.index')
                ->with('success', __('messages.shop_created'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => __('errors.server_error')]);
        }
    }

    /**
     * Обновление профиля пользователя
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            $this->userService->updateProfile(Auth::user(), $request->validated());

            return redirect()
                ->back()
                ->with('success', __('messages.profile_updated'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => __('errors.server_error')]);
        }
    }

    /**
     * Изменение пароля
     */
    public function updatePassword(ChangePasswordRequest $request)
    {
        $user = Auth::user();

        // Проверяем текущий пароль
        if (! $this->userService->checkPassword($user, $request->current_password)) {
            return redirect()
                ->back()
                ->withErrors(['current_password' => __('errors.invalid_current_password')]);
        }

        try {
            $this->userService->changePassword($user, $request->password);

            return redirect()
                ->back()
                ->with('success', __('messages.password_changed'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => __('errors.server_error')]);
        }
    }

    /**
     * Страница информации о пользователе
     */
    public function userIndex()
    {
        $user = Auth::user();

        return view('backend.cabinet.user.info', compact('user'));
    }

    /**
     * Форма изменения пароля
     */
    public function showChangePasswordForm()
    {
        return view('backend.cabinet.user.change_password');
    }

    /**
     * Форма подтверждения удаления магазина
     */
    public function deleteShop()
    {
        $user = Auth::user();

        if (! $user->seller) {
            return redirect()
                ->route('account.index')
                ->with('error', __('errors.no_seller_account'));
        }

        return view('backend.cabinet.delete_shop', compact('user'));
    }

    /**
     * Удаление магазина
     */
    public function destroyShop(Request $request)
    {
        $user = Auth::user();
        $seller = $user->seller;

        if (! $seller) {
            return redirect()
                ->route('account.index')
                ->with('error', __('errors.seller_not_found'));
        }

        // Проверка пароля
        $request->validate([
            'password' => ['required'],
        ]);

        if (! $this->userService->checkPassword($user, $request->password)) {
            return redirect()
                ->back()
                ->withErrors(['password' => __('errors.invalid_password')]);
        }

        try {
            // Проверяем через Policy
            $this->authorize('delete', $seller);

            // Удаляем магазин
            $seller->delete();

            // Логирование
            activity('sellers')
                ->causedBy($user)
                ->withProperties(['seller_name' => $seller->name])
                ->log(__('messages.shop_deleted'));

            return redirect()
                ->route('account.index')
                ->with('success', __('messages.shop_deleted'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => __('errors.server_error')]);
        }
    }

    /**
     * Загрузка логотипа магазина
     * TODO: Вынести в ImageService
     */
    private function uploadSellerLogo($seller, $file): void
    {
        $filename = 'logo_'.uniqid().'.webp';
        $path = 'sellers/'.$seller->id;

        $image = \Intervention\Image\Facades\Image::make($file)
            ->orientate()
            ->fit(400, 400, function ($c) {
                $c->upsize();
            })
            ->encode('webp', 85);

        \Storage::disk('public')->put($path.'/'.$filename, (string) $image);
        $seller->update(['photo' => $path.'/'.$filename]);
    }
}
