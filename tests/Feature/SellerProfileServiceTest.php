<?php

namespace Tests\Feature;

use App\Models\Seller;
use App\Services\SellerProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private SellerProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->service = new SellerProfileService;
    }

    public function test_update_profile_updates_seller_fields(): void
    {
        $seller = Seller::factory()->create();

        $updatedSeller = $this->service->updateProfile($seller, [
            'name' => 'Updated Shop',
            'phone' => '+123456789',
            'town' => 'Updated City',
            'sellerplace' => 'Updated Market',
            'address' => 'Updated Address',
            'description' => 'Updated Description',
            'link' => 'https://example.com',
            'link_map' => 'https://maps.example.com',
            'telegram' => '@updated',
            'instagram' => 'updated_shop',
            'viber' => '+123456789',
        ]);

        $this->assertSame('Updated Shop', $updatedSeller->name);
        $this->assertSame('https://example.com', $updatedSeller->link);
        $this->assertSame('@updated', $updatedSeller->telegram);
    }

    public function test_update_profile_replaces_logo_and_deletes_old_file(): void
    {
        $seller = Seller::factory()->create([
            'photo' => 'sellers/old-logo.webp',
        ]);
        Storage::disk('public')->put($seller->photo, 'old');

        $updatedSeller = $this->service->updateProfile(
            $seller,
            ['name' => $seller->name],
            UploadedFile::fake()->image('logo.jpg', 800, 800)
        );

        $this->assertNotNull($updatedSeller->photo);
        $this->assertNotSame('sellers/old-logo.webp', $updatedSeller->photo);
        $this->assertStringEndsWith('.webp', $updatedSeller->photo);
        Storage::disk('public')->assertMissing('sellers/old-logo.webp');
        Storage::disk('public')->assertExists($updatedSeller->photo);
    }

    public function test_update_profile_ignores_owner_product_count_and_settings(): void
    {
        $seller = Seller::factory()->create([
            'product_count' => 4,
            'auto_renew' => false,
        ]);
        $originalOwnerId = $seller->user_id;

        $updatedSeller = $this->service->updateProfile($seller, [
            'name' => 'Safe update',
            'user_id' => $originalOwnerId + 1000,
            'product_count' => 999,
            'auto_renew' => true,
        ]);

        $this->assertSame('Safe update', $updatedSeller->name);
        $this->assertSame($originalOwnerId, $updatedSeller->user_id);
        $this->assertSame(4, $updatedSeller->product_count);
        $this->assertFalse($updatedSeller->auto_renew);
    }
}
