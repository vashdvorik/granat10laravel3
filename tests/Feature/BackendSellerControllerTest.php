<?php

namespace Tests\Feature;

use App\Models\Seller;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackendSellerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_old_store_index_url_redirects_to_main_cabinet_page(): void
    {
        $user = User::factory()->create();
        Seller::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('account.store.index'))
            ->assertRedirect(route('account.index'));
    }

    public function test_main_cabinet_page_offers_to_create_shop_when_user_has_none(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('account.index'))
            ->assertOk()
            ->assertSee(route('account.store.create'), false);
    }

    public function test_store_index_uses_overview_design_system_blocks(): void
    {
        $user = User::factory()->create();
        Seller::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('account.index'));

        $response->assertOk();
        $response->assertSee('store-overview-page', false);
        $response->assertSee('store-overview-hero', false);
        $response->assertSee('store-overview-hero__stats', false);
        $response->assertSee('store-info-tile', false);
        $response->assertSee('store-tariff-card', false);
        $response->assertDontSee('account/packages', false);
    }

    public function test_store_index_shows_balance_and_no_package_controls(): void
    {
        $user = User::factory()->create(['balance' => 150]);
        Seller::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('account.index'));

        $response->assertOk()
            ->assertSee('150,00')
            ->assertDontSee('sellerAutoRenewToggle', false)
            ->assertDontSee('Текущий тариф')
            ->assertDontSee('Выбрать тариф');
    }

    public function test_store_index_shows_all_filled_contacts_without_any_package(): void
    {
        $user = User::factory()->create();
        Seller::factory()->create([
            'user_id' => $user->id,
            'link' => 'https://seller.example.com',
            'telegram' => '@seller',
            'can_order' => true,
        ]);

        $response = $this->actingAs($user)->get(route('account.index'));

        $response->assertOk();
        $response->assertSee('https://seller.example.com');
        $response->assertSee('@seller');
        $response->assertSee('Не указаны: Google Maps, Instagram, Viber');
        $response->assertDontSee('Не заполнено');
        $response->assertDontSee('скрыто тарифом');
    }

    public function test_store_edit_keeps_website_and_social_inputs_editable(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $user->id,
            'link' => 'https://seller.example.com',
            'telegram' => '@seller',
        ]);

        $response = $this->actingAs($user)->get(route('account.store.edit', $seller));

        $response->assertOk();
        $response->assertSee('store-edit-page', false);
        $response->assertSee('store-edit-form', false);
        $response->assertSee('store-edit-layout', false);
        $response->assertSee('store-edit-card', false);
        $response->assertSee('store-edit-submit-bar', false);
        $response->assertSee('store-edit-contact-group', false);
        $response->assertSee('store-telegram-connect-form', false);
        $response->assertDontSee('store-edit-actions-card', false);
        $response->assertSee('store-feature-grid', false);
        $html = $response->getContent();

        $this->assertDoesNotMatchRegularExpression('/id="link"[^>]*disabled/s', $html);
        $this->assertDoesNotMatchRegularExpression('/id="telegram"[^>]*disabled/s', $html);
        $this->assertDoesNotMatchRegularExpression('/id="instagram"[^>]*disabled/s', $html);
        $this->assertDoesNotMatchRegularExpression('/id="viber"[^>]*disabled/s', $html);
    }

    public function test_update_seller_profile_updates_fields(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put(route('account.store.update', $seller), [
            'name' => 'Updated Backend Shop',
            'phone' => '123456789',
            'settlement_id' => Settlement::factory()->create(['name' => 'Updated City'])->id,
            'sellerplace' => 'Updated Market',
            'address' => 'Updated Address',
            'description' => 'Updated Description',
            'link' => 'https://example.com',
            'link_map' => 'https://maps.example.com',
            'telegram' => '@updated',
            'instagram' => 'updated_shop',
            'viber' => '+123456789',
        ]);

        $response->assertRedirect(route('account.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('sellers', [
            'id' => $seller->id,
            'name' => 'Updated Backend Shop',
            'link' => 'https://example.com',
            'telegram' => '@updated',
        ]);
    }

    public function test_update_seller_profile_rejects_duplicate_name(): void
    {
        $user = User::factory()->create();
        Seller::factory()->create(['name' => 'Busy Shop Name']);
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put(route('account.store.update', $seller), [
            'name' => 'Busy Shop Name',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_update_seller_profile_rejects_phone_with_text(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put(route('account.store.update', $seller), [
            'name' => $seller->name,
            'phone' => 'call-me-123',
            'settlement_id' => $seller->settlement_id,
        ]);

        $response->assertSessionHasErrors(['phone']);
    }

    public function test_update_seller_profile_replaces_logo(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $user->id,
            'photo' => 'sellers/old-logo.webp',
        ]);
        Storage::disk('public')->put($seller->photo, 'old');

        $response = $this->actingAs($user)->put(route('account.store.update', $seller), [
            'name' => $seller->name,
            'settlement_id' => $seller->settlement_id,
            'logo' => UploadedFile::fake()->image('logo.png', 800, 800),
        ]);

        $response->assertRedirect(route('account.index'));

        $seller->refresh();
        $this->assertStringEndsWith('.webp', $seller->photo);
        $this->assertNotSame('sellers/old-logo.webp', $seller->photo);
        Storage::disk('public')->assertMissing('sellers/old-logo.webp');
        Storage::disk('public')->assertExists($seller->photo);
    }

    public function test_update_seller_profile_forbidden_for_other_user(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create();

        $response = $this->actingAs($user)->put(route('account.store.update', $seller), [
            'name' => 'Blocked Update',
            'settlement_id' => $seller->settlement_id,
        ]);

        $response->assertForbidden();
    }

    public function test_owner_can_enable_orders_and_delivery(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id, 'can_order' => false]);

        $this->actingAs($user)->put(route('account.store.update', $seller), [
            'name' => $seller->name,
            'settlement_id' => $seller->settlement_id,
            'can_order' => '1',
            'delivery_enabled' => '1',
            'delivery_terms' => 'По городу ежедневно.',
        ])->assertRedirect(route('account.index'));

        $seller->refresh();
        $this->assertTrue($seller->can_order);
        $this->assertTrue($seller->delivery_enabled);
        $this->assertSame('По городу ежедневно.', $seller->delivery_terms);
    }


}
