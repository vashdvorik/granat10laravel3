<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\PackageService;
use Illuminate\Support\Facades\Auth;
use Throwable;

class PackageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $seller = $user->seller;

        $packages = Package::where('is_active', true)
            ->orderBy('price')
            ->get();

        $currentPackage = $seller ? $seller->activePackage() : null;

        return view('backend.cabinet.packages.index', compact('packages', 'currentPackage', 'seller', 'user'));
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
}
