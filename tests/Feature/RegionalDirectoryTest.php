<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Seller;
use App\Models\Settlement;
use Database\Seeders\RegionsAndSettlementsSeeder;
use Database\Seeders\SellerSettlementBackfillSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionalDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_regional_directory_seeder_is_idempotent(): void
    {
        $this->seed(RegionsAndSettlementsSeeder::class);
        $this->seed(RegionsAndSettlementsSeeder::class);

        $this->assertDatabaseCount('regions', 7);
        $this->assertDatabaseCount('settlements', 40);
        $this->assertDatabaseHas('settlements', [
            'name' => 'Тирасполь',
            'slug' => 'tiraspol',
            'zip' => 'MD-3300',
        ]);
    }

    public function test_region_and_settlement_relations_work(): void
    {
        $region = Region::factory()->create();
        $settlement = Settlement::factory()->for($region)->create();

        $this->assertTrue($region->settlements->contains($settlement));
        $this->assertTrue($settlement->region->is($region));
    }

    public function test_seller_belongs_to_settlement(): void
    {
        $settlement = Settlement::factory()->create();
        $seller = Seller::factory()->for($settlement)->create([
            'town' => $settlement->name,
        ]);

        $this->assertTrue($seller->settlement->is($settlement));
        $this->assertTrue($settlement->sellers->contains($seller));
    }

    public function test_settlement_with_sellers_cannot_be_deleted(): void
    {
        $settlement = Settlement::factory()->create();
        Seller::factory()->for($settlement)->create();

        $this->expectException(\LogicException::class);

        $settlement->delete();
    }

    public function test_region_with_settlements_cannot_be_deleted(): void
    {
        $region = Region::factory()->create();
        Settlement::factory()->for($region)->create();

        $this->expectException(\LogicException::class);

        $region->delete();
    }

    public function test_active_and_ordered_scopes_exclude_inactive_records(): void
    {
        Region::factory()->create(['name' => 'Поздний', 'sort_order' => 20]);
        $first = Region::factory()->create(['name' => 'Первый', 'sort_order' => 1]);
        Region::factory()->create(['is_active' => false, 'sort_order' => 0]);

        $regions = Region::query()->active()->ordered()->get();

        $this->assertCount(2, $regions);
        $this->assertTrue($regions->first()->is($first));
    }

    public function test_existing_seller_is_backfilled_from_legacy_town(): void
    {
        $settlement = Settlement::factory()->create(['name' => 'Тирасполь']);
        $seller = Seller::factory()->create([
            'settlement_id' => null,
            'town' => 'Тирасполь',
        ]);

        $this->seed(SellerSettlementBackfillSeeder::class);

        $this->assertSame($settlement->id, $seller->refresh()->settlement_id);
    }
}
