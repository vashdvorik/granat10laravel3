<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Seller;
use App\Models\User;
use App\Services\SellerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SellerServiceTest extends TestCase
{
    use RefreshDatabase;

    private SellerService $sellerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sellerService = new SellerService;
    }

    public function test_can_create_seller(): void
    {
        $user = User::factory()->create();

        $data = [
            'name' => 'Test Shop',
            'slug' => 'test-shop',
            'description' => 'Test shop description',
        ];

        $seller = $this->sellerService->createSeller($user, $data);

        $this->assertInstanceOf(Seller::class, $seller);
        $this->assertEquals('Test Shop', $seller->name);
        $this->assertEquals($user->id, $seller->user_id);
        $this->assertTrue($seller->is_active);
    }

    public function test_can_update_seller(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

        $updated = $this->sellerService->updateSeller($seller, [
            'name' => 'New Name',
            'description' => 'New description',
        ]);

        $this->assertEquals('New Name', $updated->name);
        $this->assertEquals('New description', $updated->description);
    }

    public function test_can_get_seller_stats(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $stats = $this->sellerService->getSellerStats($seller);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_products', $stats);
        $this->assertArrayHasKey('active_products', $stats);
        $this->assertArrayHasKey('vip_products', $stats);
        $this->assertArrayHasKey('total_views', $stats);
        $this->assertArrayHasKey('has_active_package', $stats);
    }

    public function test_is_slug_unique_returns_true_for_unique_slug(): void
    {
        $result = $this->sellerService->isSlugUnique('unique-slug');

        $this->assertTrue($result);
    }

    public function test_is_slug_unique_returns_false_for_existing_slug(): void
    {
        Seller::factory()->create(['slug' => 'existing-slug']);

        $result = $this->sellerService->isSlugUnique('existing-slug');

        $this->assertFalse($result);
    }

    public function test_is_slug_unique_excludes_current_seller(): void
    {
        $seller = Seller::factory()->create(['slug' => 'test-slug']);

        // Проверяем что slug уникален если исключаем текущего продавца
        $result = $this->sellerService->isSlugUnique('test-slug', $seller->id);

        $this->assertTrue($result);
    }

    public function test_stats_are_cached(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        // Первый вызов - кэширование
        $this->sellerService->getSellerStats($seller);

        // Проверяем что кэш создан
        $this->assertTrue(Cache::has("seller.{$seller->id}.stats"));
    }

    public function test_update_seller_clears_cache(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        // Создаем кэш
        Cache::put("seller.{$seller->id}.stats", [], 60);

        // Обновляем продавца
        $this->sellerService->updateSeller($seller, ['name' => 'Updated Name']);

        // Проверяем что кэш очищен
        $this->assertFalse(Cache::has("seller.{$seller->id}.stats"));
    }
}
