<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\Seller;
use App\Policies\ProductPolicy;
use App\Policies\SellerPolicy;
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
        Product::class => ProductPolicy::class,
        Seller::class => SellerPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', fn ($user): bool => $user->hasPermission('browse_admin'));
    }
}
