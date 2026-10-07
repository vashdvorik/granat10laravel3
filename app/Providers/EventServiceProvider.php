<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Events\ProductModerationDecided;
use App\Listeners\SendNewOrderNotification;
use App\Listeners\SendOrderStatusChangedNotification;
use App\Listeners\SendOrderTelegramNotification;
use App\Listeners\SendProductModerationDecisionNotification;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\Seller;
use App\Observers\CategoryObserver;
use App\Observers\ImageObserver;
use App\Observers\ProductObserver;
use App\Observers\SellerObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            \App\Listeners\LogUserRegistered::class,
        ],
        Login::class => [
            \App\Listeners\LogSuccessfulLogin::class,
        ],
        Logout::class => [
            \App\Listeners\LogSuccessfulLogout::class,
        ],
        ProductModerationDecided::class => [
            SendProductModerationDecisionNotification::class,
        ],
        OrderCreated::class => [
            SendNewOrderNotification::class,
            SendOrderTelegramNotification::class,
        ],
        OrderStatusChanged::class => [
            SendOrderStatusChangedNotification::class,
            SendOrderTelegramNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        Category::observe(CategoryObserver::class);
        Image::observe(ImageObserver::class);
        Product::observe(ProductObserver::class);
        Seller::observe(SellerObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
