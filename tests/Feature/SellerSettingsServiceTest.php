<?php

namespace Tests\Feature;

use App\Models\Seller;
use App\Services\SellerSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SellerSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SellerSettingsService();
    }

    public function test_update_auto_renew_enables_setting(): void
    {
        $seller = Seller::factory()->create(['auto_renew' => false]);

        $updatedSeller = $this->service->updateAutoRenew($seller, true);

        $this->assertTrue($updatedSeller->auto_renew);
        $this->assertTrue($seller->fresh()->auto_renew);
    }

    public function test_update_auto_renew_disables_setting(): void
    {
        $seller = Seller::factory()->create(['auto_renew' => true]);

        $updatedSeller = $this->service->updateAutoRenew($seller, false);

        $this->assertFalse($updatedSeller->auto_renew);
        $this->assertFalse($seller->fresh()->auto_renew);
    }
}
