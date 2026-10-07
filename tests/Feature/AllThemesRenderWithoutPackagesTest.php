<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Image;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Services\CabinetDesignManager;
use App\Services\DesignManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllThemesRenderWithoutPackagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_pages_render_in_all_theme_combinations(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $user = User::factory()->create(['balance' => 321]);
        $seller = Seller::factory()->create(['user_id' => $user->id, 'can_order' => true, 'link' => 'https://a.test', 'telegram' => '@a']);
        $category = Category::factory()->create();
        $vip = Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'is_vip' => true, 'vip_until' => now()->addDays(3)]);
        Image::factory()->main()->create(['product_id' => $vip->id]);
        $plain = Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'can_order' => true]);
        Image::factory()->main()->create(['product_id' => $plain->id]);
        $draft = Product::factory()->draft()->create(['seller_id' => $seller->id, 'category_id' => $category->id]);
        $order = Order::factory()->create(['seller_id' => $seller->id]);
        foreach (Image::all() as $img) {
            \Illuminate\Support\Facades\Storage::disk('public')->put($img->path, 'x');
        }

        $public = [
            route('home'), route('catalog.index'), route('catalog.show', $category->alias), route('catalog.sale'),
            route('search', ['q' => 'a']), route('favorites'), route('product.show', $vip->alias),
            route('product.show', $plain->alias), route('shops'), route('seller', $seller->alias),
        ];
        $cabinet = [
            route('account.index'), route('account.stats'),
            route('account.store.products.index'), route('account.store.products.create'),
            route('account.store.products.show', $vip), route('account.store.products.show', $plain),
            route('account.store.products.show', $draft),
            route('account.store.products.edit', $vip), route('account.store.products.edit', $plain),
            route('account.store.edit', $seller), route('account.store.events.index'),
            route('account.store.orders.index'), route('account.store.orders.show', $order),
            route('account.user.index'), route('account.store.delete'),
        ];

        foreach (['legacy', 'new', 'joom'] as $publicTheme) {
            app(DesignManager::class)->setActive($publicTheme);
            foreach ($public as $url) {
                $this->get($url)->assertStatus(200);
            }
        }

        foreach (['legacy', 'new'] as $cabinetTheme) {
            app(CabinetDesignManager::class)->setActive($cabinetTheme);
            foreach ($cabinet as $url) {
                $response = $this->actingAs($user)->get($url);
                $this->assertSame(200, $response->status(), "{$cabinetTheme}: {$url}");
                $html = $response->getContent();
                $this->assertStringNotContainsString('account/packages', $html, "{$cabinetTheme}: {$url}");
                $this->assertStringNotContainsString('Выбрать тариф', $html, "{$cabinetTheme}: {$url}");
            }
        }

        // пользователь без магазина
        $lonely = User::factory()->create();
        foreach (['legacy', 'new'] as $cabinetTheme) {
            app(CabinetDesignManager::class)->setActive($cabinetTheme);
            foreach ([route('account.index'), route('account.store.create'), route('account.user.index')] as $url) {
                $this->actingAs($lonely)->get($url)->assertStatus(200);
            }
        }
    }
}
