<?php

namespace App\Providers;

use App\Models\BalanceTopup;
use App\Models\BalanceTransaction;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Region;
use App\Models\Seller;
use App\Models\Settlement;
use App\Models\User;
use App\Models\VipPurchase;
use App\Policies\CategoryPolicy;
use App\Policies\FinanceRecordPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\RegionPolicy;
use App\Policies\SellerPolicy;
use App\Policies\SettlementPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        BalanceTopup::class => FinanceRecordPolicy::class,
        BalanceTransaction::class => FinanceRecordPolicy::class,
        Category::class => CategoryPolicy::class,
        Order::class => OrderPolicy::class,
        Product::class => ProductPolicy::class,
        Region::class => RegionPolicy::class,
        Seller::class => SellerPolicy::class,
        Settlement::class => SettlementPolicy::class,
        User::class => UserPolicy::class,
        VipPurchase::class => FinanceRecordPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', fn ($user): bool => $user->hasPermission('browse_admin'));
    }
}
