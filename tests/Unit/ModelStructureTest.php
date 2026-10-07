<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\Seller;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тест: структура моделей — fillable, casts, связи.
 * Не требует БД или бутстрапа Laravel.
 *
 * @covers \App\Models\Product
 * @covers \App\Models\Seller
 * @covers \App\Models\Category
 * @covers \App\Models\Image
 */
class ModelStructureTest extends TestCase
{
    // =========================================================================
    // Product
    // =========================================================================

    public function test_product_fillable_contains_required_fields(): void
    {
        $product = new Product;
        $fillable = $product->getFillable();

        foreach (['name', 'price', 'alias', 'is_vip', 'is_active', 'category_id', 'seller_id'] as $field) {
            $this->assertContains($field, $fillable, "Product fillable должен содержать поле '{$field}'");
        }
    }

    public function test_product_guarded_contains_id(): void
    {
        $product = new Product;
        $this->assertContains('id', $product->getGuarded());
    }

    public function test_product_casts_datetime_fields(): void
    {
        $product = new Product;
        $casts = $product->getCasts();

        $this->assertArrayHasKey('push_at', $casts);
        $this->assertArrayHasKey('sale_started_at', $casts);
    }

    // =========================================================================
    // Seller
    // =========================================================================

    public function test_seller_fillable_contains_core_fields(): void
    {
        $seller = new Seller;
        $fillable = $seller->getFillable();

        foreach (['user_id', 'name', 'alias', 'phone', 'town'] as $field) {
            $this->assertContains($field, $fillable, "Seller fillable должен содержать '{$field}'");
        }
    }

    public function test_seller_product_count_is_guarded(): void
    {
        $seller = new Seller;
        $this->assertContains('product_count', $seller->getGuarded(), 'product_count должен быть guarded');
    }

    public function test_seller_casts_booleans(): void
    {
        $seller = new Seller;
        $casts = $seller->getCasts();

        foreach (['can_order', 'delivery_enabled', 'telegram_notifications_enabled'] as $field) {
            $this->assertArrayHasKey($field, $casts);
            $this->assertSame('boolean', $casts[$field]);
        }
        $this->assertArrayNotHasKey('auto_renew', $casts);
    }

    // =========================================================================
    // Category
    // =========================================================================

    public function test_category_fillable_and_casts_support_filament_management(): void
    {
        $category = new Category;
        $fillable = $category->getFillable();
        $casts = $category->getCasts();

        foreach (['name', 'alias', 'parent_id', 'number', 'is_active'] as $field) {
            $this->assertContains($field, $fillable);
        }

        $this->assertSame('boolean', $casts['is_active']);
        $this->assertSame('integer', $casts['number']);
    }

    // =========================================================================
    // Image
    // =========================================================================

    public function test_image_fillable(): void
    {
        $image = new Image;
        $fillable = $image->getFillable();

        $this->assertContains('product_id', $fillable);
        $this->assertContains('path', $fillable);
        $this->assertContains('is_main', $fillable);
    }

    // =========================================================================
    // Методы существования Scope-ов
    // =========================================================================

    public function test_category_has_get_cached_root_tree_method(): void
    {
        $this->assertTrue(method_exists(Category::class, 'getCachedRootTree'));
    }

    public function test_seller_does_not_contain_product_limit_logic(): void
    {
        $this->assertFalse(method_exists(Seller::class, 'canActivateProduct'));
        $this->assertFalse(method_exists(Seller::class, 'checkProductLimits'));
        $this->assertFalse(method_exists(Seller::class, 'getPackageStatus'));
        $this->assertFalse(method_exists(Seller::class, 'activePackage'));
        $this->assertFalse(method_exists(Seller::class, 'sellerPackages'));
    }

    public function test_seller_has_get_cached_by_alias_method(): void
    {
        $this->assertTrue(method_exists(Seller::class, 'getCachedByAlias'));
    }

    public function test_product_has_get_cached_price_range_method(): void
    {
        $this->assertTrue(method_exists(Product::class, 'getCachedPriceRange'));
    }
}
