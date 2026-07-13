<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendPackageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_packages_page_shows_compatibility_reason_before_purchase(): void
    {
        $user = User::factory()->create(['balance' => 1000]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        Package::factory()->create([
            'name' => 'Малый тариф',
            'price' => 0,
            'active_products_limit' => 1,
            'vip_products_limit' => 5,
        ]);
        Product::factory()->count(3)->create([
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('account.packages.index'));

        $response->assertOk();
        $response->assertSee('package-page-hero', false);
        $response->assertSee('package-card--needs_preparation', false);
        $response->assertSee('package-compatibility', false);
        $response->assertSee('Перед переходом нужно подготовить товары');
        $response->assertSee('Активных товаров 3, тариф разрешает 1. Нужно снять с публикации 2.');
        $response->assertSee('Подготовить переход');
        $response->assertSee(route('account.packages.prepare', Package::first()), false);
    }

    public function test_packages_page_does_not_render_dead_top_up_link(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        Package::factory()->create([
            'name' => 'Платный тариф',
            'price' => 100,
        ]);

        $response = $this->actingAs($user)->get(route('account.packages.index'));

        $response->assertOk();
        $response->assertSee('Баланса недостаточно для подключения');
        $response->assertSee('Пополнение пока не подключено');
        $response->assertDontSee('window.location.href =', false);
        $response->assertDontSee('btn btn-success btn-squared">Пополнить баланс', false);
    }

    public function test_prepare_page_shows_products_that_can_stay_active(): void
    {
        $user = User::factory()->create(['balance' => 1000]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create([
            'name' => 'Компактный',
            'price' => 0,
            'active_products_limit' => 1,
            'vip_products_limit' => 0,
        ]);
        $firstProduct = Product::factory()->create([
            'seller_id' => $seller->id,
            'name' => 'Первый активный товар',
            'is_active' => true,
        ]);
        Product::factory()->create([
            'seller_id' => $seller->id,
            'name' => 'Второй активный товар',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('account.packages.prepare', $package));

        $response->assertOk();
        $response->assertSee('package-prepare-hero', false);
        $response->assertSee('package-prepare-product', false);
        $response->assertSee('package-prepare-confirm', false);
        $response->assertSee('Подготовить переход на тариф «Компактный»');
        $response->assertSee('Выберите товары, которые останутся активными');
        $response->assertSee('Первый активный товар');
        $response->assertSee('Второй активный товар');
        $response->assertSee('name="active_product_ids[]"', false);
        $response->assertSee('value="'.$firstProduct->id.'"', false);
    }

    public function test_apply_prepared_transition_updates_products_and_activates_package(): void
    {
        $user = User::factory()->create(['balance' => 500]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create([
            'name' => 'Малый',
            'price' => 100,
            'active_products_limit' => 1,
            'vip_products_limit' => 0,
        ]);
        $keptProduct = Product::factory()->activeVip()->create(['seller_id' => $seller->id]);
        $disabledProduct = Product::factory()->activeVip()->create(['seller_id' => $seller->id]);

        $response = $this->actingAs($user)->post(route('account.packages.prepare.apply', $package), [
            'active_product_ids' => [$keptProduct->id],
            'vip_product_ids' => [],
        ]);

        $response->assertRedirect(route('account.packages.index'));
        $response->assertSessionHas('success');
        $this->assertSame($package->id, $seller->activePackage()->package_id);
        $this->assertEquals(400, $user->fresh()->balance);
        $this->assertTrue((bool) $keptProduct->fresh()->is_active);
        $this->assertFalse((bool) $keptProduct->fresh()->is_vip);
        $this->assertFalse((bool) $disabledProduct->fresh()->is_active);
    }
}
