<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Services\PackageService;
use App\Services\PackageTransitionPreparationService;
use App\Services\ProductLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PackageTransitionPreparationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_deactivates_unselected_products_removes_extra_vip_and_purchases_package(): void
    {
        $user = User::factory()->create(['balance' => 1000]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create([
            'price' => 100,
            'active_products_limit' => 1,
            'vip_products_limit' => 0,
        ]);
        $keptProduct = Product::factory()->activeVip()->create(['seller_id' => $seller->id]);
        $disabledProduct = Product::factory()->activeVip()->create(['seller_id' => $seller->id]);
        $disabledRegularProduct = Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
            'publication_requested' => true,
        ]);

        $sellerPackage = $this->service()->apply(
            $user,
            $seller,
            $package,
            [$keptProduct->id],
            []
        );

        $this->assertSame($package->id, $sellerPackage->package_id);
        $this->assertTrue((bool) $sellerPackage->is_active);
        $this->assertEquals(900, $user->fresh()->balance);
        $this->assertTrue((bool) $keptProduct->fresh()->is_active);
        $this->assertFalse((bool) $keptProduct->fresh()->is_vip);
        $this->assertFalse((bool) $disabledProduct->fresh()->is_active);
        $this->assertFalse((bool) $disabledProduct->fresh()->publication_requested);
        $this->assertFalse((bool) $disabledRegularProduct->fresh()->is_active);
        $this->assertFalse((bool) $disabledRegularProduct->fresh()->publication_requested);
    }

    public function test_apply_rejects_too_many_selected_active_products(): void
    {
        $user = User::factory()->create(['balance' => 1000]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create([
            'price' => 0,
            'active_products_limit' => 1,
            'vip_products_limit' => 10,
        ]);
        $products = Product::factory()->count(2)->create([
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);

        $this->service()->apply(
            $user,
            $seller,
            $package,
            $products->pluck('id')->all(),
            []
        );
    }

    private function service(): PackageTransitionPreparationService
    {
        return new PackageTransitionPreparationService(
            new PackageService(new ProductLimitService())
        );
    }
}
