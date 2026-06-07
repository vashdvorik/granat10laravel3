<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\PackagePolicy;
use App\Policies\ProductPolicy;
use App\Policies\SellerPackagePolicy;
use App\Policies\SellerPolicy;
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
        Category::class => CategoryPolicy::class,
        Package::class => PackagePolicy::class,
        Product::class => ProductPolicy::class,
        Seller::class => SellerPolicy::class,
        SellerPackage::class => SellerPackagePolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', fn ($user): bool => $user->hasPermission('browse_admin'));
    }
}
