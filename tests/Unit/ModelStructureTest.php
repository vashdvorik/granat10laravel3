<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Image;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тест: структура моделей — fillable, casts, связи.
 * Не требует БД или бутстрапа Laravel.
 *
 * @covers \App\Models\Product
 * @covers \App\Models\Seller
 * @covers \App\Models\Package
 * @covers \App\Models\SellerPackage
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
        $product = new Product();
        $fillable = $product->getFillable();

        foreach (['name', 'price', 'alias', 'is_vip', 'is_active', 'category_id', 'seller_id'] as $field) {
            $this->assertContains($field, $fillable, "Product fillable должен содержать поле '{$field}'");
        }
    }

    public function test_product_fillable_does_not_contain_id(): void
    {
        $product = new Product();
        $fillable = $product->getFillable();
        
        $this->assertNotContains('id', $fillable, "Product fillable не должен содержать поле 'id' для защиты от mass assignment");
    }

    public function test_product_casts_datetime_fields(): void
    {
        $product = new Product();
        $casts = $product->getCasts();

        $this->assertArrayHasKey('push_at', $casts);
        $this->assertArrayHasKey('sale_started_at', $casts);
    }

    // =========================================================================
    // Seller
    // =========================================================================

    public function test_seller_fillable_contains_core_fields(): void
    {
        $seller = new Seller();
        $fillable = $seller->getFillable();

        foreach (['user_id', 'name', 'alias', 'phone', 'town'] as $field) {
            $this->assertContains($field, $fillable, "Seller fillable должен содержать '{$field}'");
        }
    }

    public function test_seller_product_count_is_not_fillable(): void
    {
        $seller = new Seller();
        $fillable = $seller->getFillable();
        
        $this->assertNotContains('product_count', $fillable, 'product_count не должен быть в fillable для защиты от mass assignment');
    }

    public function test_seller_casts_booleans(): void
    {
        $seller = new Seller();
        $casts = $seller->getCasts();

        $this->assertArrayHasKey('auto_renew', $casts);
        $this->assertSame('boolean', $casts['auto_renew']);
    }

    // =========================================================================
    // Package
    // =========================================================================

    public function test_package_casts_boolean_features(): void
    {
        $package = new Package();
        $casts = $package->getCasts();

        foreach (['has_website_link', 'has_social_links', 'has_order_system', 'is_active'] as $field) {
            $this->assertArrayHasKey($field, $casts, "Package должен кастить '{$field}'");
            $this->assertSame('boolean', $casts[$field]);
        }
    }

    public function test_package_fillable_contains_limit_fields(): void
    {
        $package = new Package();
        $fillable = $package->getFillable();

        $this->assertContains('active_products_limit', $fillable);
        $this->assertContains('vip_products_limit', $fillable);
    }

    // =========================================================================
    // SellerPackage
    // =========================================================================

    public function test_seller_package_casts_dates(): void
    {
        $sp = new SellerPackage();
        $casts = $sp->getCasts();

        $this->assertArrayHasKey('start_date', $casts, 'start_date должен быть кастирован');
        $this->assertArrayHasKey('end_date', $casts, 'end_date должен быть кастирован');
    }

    public function test_seller_package_fillable(): void
    {
        $sp = new SellerPackage();
        $fillable = $sp->getFillable();

        foreach (['seller_id', 'package_id', 'start_date', 'end_date', 'is_active'] as $field) {
            $this->assertContains($field, $fillable);
        }
    }

    // =========================================================================
    // Category
    // =========================================================================

    public function test_category_fillable_contains_seo_fields(): void
    {
        $category = new Category();
        $fillable = $category->getFillable();

        foreach (['name', 'alias', 'parent_id', 'number'] as $field) {
            $this->assertContains($field, $fillable);
        }
    }

    // =========================================================================
    // Image
    // =========================================================================

    public function test_image_fillable(): void
    {
        $image = new Image();
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

    public function test_seller_has_can_activate_product_method(): void
    {
        $this->assertTrue(method_exists(Seller::class, 'canActivateProduct'));
    }

    public function test_seller_has_check_product_limits_method(): void
    {
        $this->assertTrue(method_exists(Seller::class, 'checkProductLimits'));
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
