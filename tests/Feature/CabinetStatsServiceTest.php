<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Services\CabinetStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CabinetStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    private CabinetStatsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CabinetStatsService();
    }

    public function test_get_stats_returns_product_counts_views_and_balance(): void
    {
        $user = User::factory()->create(['balance' => 1234]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $regularProduct = Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
            'is_vip' => false,
            'lowprice' => null,
        ]);
        $vipProduct = Product::factory()->activeVip()->create([
            'seller_id' => $seller->id,
            'lowprice' => 50,
        ]);
        Product::factory()->inactive()->create([
            'seller_id' => $seller->id,
        ]);

        DB::table('views')->insert([
            ['product_id' => $regularProduct->id, 'views' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $vipProduct->id, 'views' => 8, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $stats = $this->service->getStats($user, $seller);

        $this->assertSame(3, $stats['totalProducts']);
        $this->assertSame(2, $stats['activeProducts']);
        $this->assertSame(1, $stats['inactiveProducts']);
        $this->assertSame(1, $stats['vipProducts']);
        $this->assertSame(1, $stats['saleProducts']);
        $this->assertSame(11, (int) $stats['totalViews']);
        $this->assertSame($vipProduct->id, $stats['topProducts']->first()->id);
        $this->assertEquals(1234, $stats['balance']);
    }

    public function test_get_stats_for_empty_shop_returns_zeroes_and_empty_top_products(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $stats = $this->service->getStats($user, $seller);

        $this->assertSame(0, $stats['totalProducts']);
        $this->assertSame(0, $stats['activeProducts']);
        $this->assertSame(0, $stats['inactiveProducts']);
        $this->assertSame(0, $stats['vipProducts']);
        $this->assertSame(0, $stats['saleProducts']);
        $this->assertSame(0, $stats['totalViews']);
        $this->assertTrue($stats['topProducts']->isEmpty());
    }
}
