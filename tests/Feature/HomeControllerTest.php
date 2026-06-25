<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_dynamic_marketplace_sections(): void
    {
        $category = Category::factory()->create(['name' => 'Категория главной']);
        $seller = Seller::factory()->create(['name' => 'Магазин главной']);
        $package = Package::factory()->withLimits(50, 10)->create();

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => null,
        ]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Товар главной',
            'price' => 1000,
            'lowprice' => 800,
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Популярные категории')
            ->assertSee('Новые товары')
            ->assertSee('Выгодные предложения')
            ->assertSee('Магазины рядом')
            ->assertSee($category->name)
            ->assertSee($seller->name)
            ->assertSee($product->name)
            ->assertDontSee('Power to the pro')
            ->assertDontSee('Shop now');
    }
}
