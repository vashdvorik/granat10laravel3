<?php

namespace Tests\Unit;

use App\Models\Region;
use App\Models\Settlement;
use App\Services\RegionalContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionalContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_defaults_to_all_regions(): void
    {
        $context = app(RegionalContext::class);

        $this->assertTrue($context->isAllRegions());
        $this->assertNull($context->settlement());
        $this->assertNull($context->region());
        $this->assertSame('Все регионы', $context->label());
    }

    public function test_context_exposes_selected_settlement_and_region(): void
    {
        $region = Region::factory()->create();
        $settlement = Settlement::factory()->for($region)->create();
        $context = app(RegionalContext::class);

        $context->setSettlement($settlement->load('region'));

        $this->assertFalse($context->isAllRegions());
        $this->assertTrue($context->settlement()->is($settlement));
        $this->assertTrue($context->region()->is($region));
        $this->assertSame($settlement->name, $context->label());
    }
}
