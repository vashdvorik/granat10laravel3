<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\PreparePackageTransitionRequest;
use App\Models\Package;
use App\Services\PackageCompatibilityService;
use App\Services\PackageService;
use App\Services\PackageTransitionPreparationService;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class PackageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(PackageCompatibilityService $packageCompatibilityService)
    {
        $user = Auth::user();
        $seller = $user->seller;

        $packages = Package::where('is_active', true)
            ->orderBy('price')
            ->get();

        $currentPackage = $seller ? $seller->activePackage() : null;
        $packageCompatibilities = $packages
            ->mapWithKeys(fn (Package $package): array => [
                $package->id => $packageCompatibilityService->make($user, $seller, $package),
            ]);

        return view('backend.cabinet.packages.index', compact(
            'packages',
            'currentPackage',
            'seller',
            'user',
            'packageCompatibilities'
        ));
    }

    public function purchase(Package $package, PackageService $packageService)
    {
        $user = Auth::user();
        $seller = $user->seller;
        $balanceBefore = $user->balance;

        if (!$seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }

        $this->authorize('purchasePackage', $seller);

        try {
            $packageService->purchase($user, $seller, $package);

            activity('packages')
                ->causedBy($user)
                ->performedOn($package)
                ->withProperties([
                    'seller_id' => $seller->id,
                    'seller_name' => $seller->name,
                    'package_name' => $package->name,
                    'price' => $package->price,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $user->fresh()->balance,
                ])
                ->log('Активирован тариф "' . $package->name . '"');

            return redirect()->route('account.packages.index')
                ->with('success', 'Тариф "' . $package->name . '" успешно активирован!');
        } catch (Throwable $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function prepare(
        Package $package,
        PackageCompatibilityService $packageCompatibilityService,
        PackageTransitionPreparationService $preparationService
    ) {
        $user = Auth::user();
        $seller = $user->seller;

        if (!$seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }

        $this->authorize('purchasePackage', $seller);

        $compatibility = $packageCompatibilityService->make($user, $seller, $package);

        if ($compatibility->status === 'current') {
            return redirect()->route('account.packages.index')
                ->with('error', 'Этот тариф уже активен.');
        }

        if ($compatibility->status === 'insufficient_balance') {
            return redirect()->route('account.packages.index')
                ->with('error', 'Недостаточно средств на балансе.');
        }

        if ($compatibility->status !== 'needs_preparation') {
            return redirect()->route('account.packages.index')
                ->with('error', 'Этот тариф можно подключить без подготовки товаров.');
        }

        $activeProducts = $preparationService->activeProducts($seller);

        return view('backend.cabinet.packages.prepare', compact(
            'package',
            'seller',
            'user',
            'compatibility',
            'activeProducts'
        ));
    }

    public function applyPreparation(
        PreparePackageTransitionRequest $request,
        Package $package,
        PackageTransitionPreparationService $preparationService
    ) {
        $user = Auth::user();
        $seller = $user->seller;
        $balanceBefore = $user->balance;

        if (!$seller) {
            return redirect()->route('account.store.create')
                ->with('error', 'Сначала создайте магазин');
        }

        $this->authorize('purchasePackage', $seller);

        try {
            $preparationService->apply(
                $user,
                $seller,
                $package,
                $request->activeProductIds(),
                $request->vipProductIds()
            );

            activity('packages')
                ->causedBy($user)
                ->performedOn($package)
                ->withProperties([
                    'seller_id' => $seller->id,
                    'seller_name' => $seller->name,
                    'package_name' => $package->name,
                    'price' => $package->price,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $user->fresh()->balance,
                    'prepared_transition' => true,
                ])
                ->log('Подготовлен переход и активирован тариф "' . $package->name . '"');

            return redirect()->route('account.packages.index')
                ->with('success', 'Тариф "' . $package->name . '" активирован. Лишние товары и VIP-метки обновлены.');
        } catch (RuntimeException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}
