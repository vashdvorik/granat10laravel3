<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature-тест: личный кабинет продавца (/account/*).
 *
 * @covers \App\Http\Controllers\CabinetController::cabinet
 * @covers \App\Http\Controllers\CabinetController::createShop
 * @covers \App\Http\Controllers\CabinetController::storeShop
 * @covers \App\Http\Controllers\CabinetController::updatePassword
 */
class CabinetControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // =========================================================================
    // Гость (неаутентифицированный пользователь)
    // =========================================================================

    public function test_cabinet_redirects_guest_to_login(): void
    {
        $response = $this->get(route('account.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_create_shop_redirects_guest_to_login(): void
    {
        $response = $this->get(route('account.store.create'));

        $response->assertRedirect(route('login'));
    }

    // =========================================================================
    // Аутентифицированный пользователь без магазина
    // =========================================================================

    public function test_cabinet_returns_200_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('account.index'));

        $response->assertStatus(200);
    }

    public function test_cabinet_uses_seller_design_system_styles(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('account.index'));

        $response
            ->assertStatus(200)
            ->assertSee('css/seller-cabinet.css', false)
            ->assertSee('?v=', false)
            ->assertSee('seller-cabinet-body', false)
            ->assertSee('syncSellerSidebarState', false)
            ->assertSee('seller-sidebar-collapsed', false);

        $css = file_get_contents(public_path('css/seller-cabinet.css'));

        $this->assertStringContainsString('--seller-cabinet-accent: #9152c7;', $css);
        $this->assertStringContainsString('--seller-cabinet-font: "Inter"', $css);
        $this->assertStringContainsString('--seller-cabinet-font-size: 16px;', $css);
        $this->assertStringContainsString('--seller-cabinet-heading-weight: 700;', $css);
        $this->assertStringContainsString('body.seller-cabinet-body .btn-primary', $css);
        $this->assertStringContainsString('background-clip: padding-box;', $css);
        $this->assertStringContainsString('body.seller-cabinet-body .card > .card-header:first-child', $css);
        $this->assertStringContainsString('border-top-left-radius: calc(var(--seller-cabinet-radius) - 1px) !important;', $css);
        $this->assertStringContainsString('body.seller-cabinet-body .card > .card-footer:last-child', $css);
        $this->assertStringContainsString('border-bottom-right-radius: calc(var(--seller-cabinet-radius) - 1px) !important;', $css);
        $this->assertStringContainsString('body.seller-cabinet-body .header-top', $css);
        $this->assertStringContainsString('z-index: 10020;', $css);
        $this->assertStringContainsString('body.seller-cabinet-body .logo-area', $css);
        $this->assertStringContainsString('width: 280px;', $css);
        $this->assertStringContainsString('body.seller-cabinet-body.seller-sidebar-collapsed .logo-area', $css);
        $this->assertStringContainsString('overflow: hidden;', $css);
        $this->assertStringContainsString('body.seller-cabinet-body .navbar-left .navbar-brand .dark', $css);
        $this->assertStringContainsString('body.seller-cabinet-body.seller-sidebar-collapsed .navbar-left .navbar-brand', $css);
        $this->assertStringContainsString('display: none !important;', $css);
        $this->assertStringContainsString('body.seller-cabinet-body .sidebar__menu-group ul.sidebar_nav li ul li > a', $css);
        $this->assertStringContainsString('margin-left: 0 !important;', $css);
        $this->assertStringContainsString('body.seller-cabinet-body .sidebar-wrapper', $css);
        $this->assertStringContainsString('top: 72px;', $css);
        $this->assertStringContainsString('height: calc(100vh - 72px);', $css);
        $this->assertStringContainsString('margin-top: 16px;', $css);
        $this->assertStringContainsString('body.seller-cabinet-body:has(.contents.expanded) .logo-area', $css);
        $this->assertStringContainsString('@media (max-width: 767.98px)', $css);
        $this->assertStringContainsString('body.seller-cabinet-body .sidebar.collapsed', $css);
    }

    public function test_cabinet_shows_unified_renewal_block_instead_of_legacy_package_alert(): void
    {
        $user = User::factory()->create(['balance' => 500]);
        $seller = Seller::factory()->create([
            'user_id' => $user->id,
            'auto_renew' => true,
        ]);
        $package = Package::factory()->create([
            'price' => 200,
            'duration_days' => 30,
        ]);
        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => now()->addDays(3)->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('account.index'));

        $response->assertOk();
        $response->assertSee('seller-cabinet-renewal', false);
        $response->assertSee('seller-cabinet-renewal-note', false);
        $response->assertDontSee('autoRenewSwitch', false);
        $response->assertDontSee('toggleAutoRenew(this)', false);
    }

    public function test_create_shop_form_returns_200_when_no_shop(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('account.store.create'));

        $response
            ->assertStatus(200)
            ->assertSee('Район / ориентир')
            ->assertSee('name="sellerplace"', false)
            ->assertSee('store-create-logo-upload__placeholder', false)
            ->assertDontSee('cabinet/img/author/profile.png', false)
            ->assertDontSee('<option value="Центр">', false)
            ->assertDontSee('<option value="Балка">', false);

        $html = $response->getContent();
        $formStart = strpos($html, 'id="create-shop-form"');
        $logoInput = strpos($html, 'id="shop-logo-upload"');
        $formEnd = $formStart === false ? false : strpos($html, '</form>', $formStart);

        $this->assertTrue(
            $formStart !== false && $logoInput !== false && $formEnd !== false
                && $formStart < $logoInput && $logoInput < $formEnd,
            'Логотип должен находиться внутри формы создания магазина.',
        );
    }

    public function test_create_shop_redirects_if_shop_already_exists(): void
    {
        $user   = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('account.store.create'));

        // Магазин уже создан — редирект с ошибкой
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // =========================================================================
    // Создание магазина
    // =========================================================================

    public function test_store_shop_creates_seller_and_assigns_base_package(): void
    {
        // Базовый пакет ID=1 должен существовать
        $basePackage = Package::factory()->free()->create(['id' => null]); // создаём новый
        // Для теста storeShop нужен package id=1 — создадим его если не существует
        if (!Package::find(1)) {
            Package::factory()->free()->create(['id' => 1]);
        }

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('account.store.store'), [
            'name'        => 'Мой тестовый магазин Уникальный 99Z',
            'phone'       => '+7-999-999-99-99',
            'settlement_id' => Settlement::factory()->create(['name' => 'Москва'])->id,
            'sellerplace' => 'Центральный рынок',
            'address'     => 'ул. Тестовая, д. 1',
            'description' => 'Описание нашего магазина',
        ]);

        $response->assertRedirect(route('account.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sellers', [
            'user_id' => $user->id,
            'name'    => 'Мой тестовый магазин Уникальный 99Z',
        ]);

        // После создания магазина должен быть создан SellerPackage
        $seller = Seller::where('user_id', $user->id)->first();
        $this->assertNotNull($seller);
        $this->assertTrue($seller->sellerPackages()->exists(), 'После создания магазина должен быть назначен базовый пакет');
    }

    public function test_store_shop_validates_name_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('account.store.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_shop_validates_name_unique(): void
    {
        Seller::factory()->create(['name' => 'УникальноеИмяМагазина_Занято']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('account.store.store'), [
            'name' => 'УникальноеИмяМагазина_Занято',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_shop_prevents_duplicate_shop_per_user(): void
    {
        $user   = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('account.store.store'), [
            'name' => 'Второй Магазин Попытка',
            'settlement_id' => $seller->settlement_id,
        ]);

        $response->assertRedirect(route('account.index'));
        $response->assertSessionHas('error');
    }

    public function test_store_shop_generates_unique_alias_from_name(): void
    {
        if (!Package::find(1)) {
            Package::factory()->free()->create(['id' => 1]);
        }

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.store.store'), [
            'name' => 'Мой Тест Магазин Алиас 007',
            'settlement_id' => Settlement::factory()->create()->id,
        ]);

        $seller = Seller::where('user_id', $user->id)->first();

        $this->assertNotNull($seller->alias);
        $this->assertNotEmpty($seller->alias);
    }

    public function test_stats_page_returns_service_view_data(): void
    {
        $user = User::factory()->create(['balance' => 500]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->withLimits(5, 1)->create();
        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);

        DB::table('views')->insert([
            'product_id' => $product->id,
            'views' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('account.stats'));

        $response->assertStatus(200);
        $response->assertViewHas('totalProducts', 1);
        $response->assertViewHas('activeProducts', 1);
        $response->assertViewHas('totalViews', 7);
        $response->assertViewHas('productLimit', 5);
        $response->assertViewHas('balance', '500.00');
    }

    // =========================================================================
    // Смена пароля
    // =========================================================================

    public function test_update_password_changes_password_successfully(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123'),
        ]);

        $response = $this->actingAs($user)->post(route('account.user.update_password'), [
            'current_password'      => 'OldPassword123',
            'password'              => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ]);

        $response->assertRedirect(route('account.index'));
        $response->assertSessionHas('success');

        // Убеждаемся, что пароль действительно сменён
        $this->assertTrue(Hash::check('NewPassword456', $user->fresh()->password));
    }

    public function test_update_password_fails_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('RealPassword123'),
        ]);

        $response = $this->actingAs($user)->post(route('account.user.update_password'), [
            'current_password'      => 'WrongPassword000',
            'password'              => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ]);

        $response->assertRedirect(route('account.index'));
        $response->assertSessionHas('error');

        // Пароль не должен был измениться
        $this->assertTrue(Hash::check('RealPassword123', $user->fresh()->password));
    }

    public function test_update_password_validates_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPass123'),
        ]);

        $response = $this->actingAs($user)->post(route('account.user.update_password'), [
            'current_password'      => 'OldPass123',
            'password'              => 'NewPassword456',
            'password_confirmation' => 'DifferentPassword789',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_update_password_requires_minimum_8_chars(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPass123'),
        ]);

        $response = $this->actingAs($user)->post(route('account.user.update_password'), [
            'current_password'      => 'OldPass123',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_destroy_shop_deletes_current_users_shop(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123'),
        ]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create();
        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
        ]);

        $response = $this->actingAs($user)->delete(route('account.store.destroy'), [
            'password' => 'Password123',
        ]);

        $response->assertRedirect(route('account.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('sellers', ['id' => $seller->id]);
        $this->assertDatabaseMissing('seller_packages', ['seller_id' => $seller->id]);
    }

    public function test_destroy_shop_rejects_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123'),
        ]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('account.store.destroy'), [
            'password' => 'WrongPassword',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('sellers', ['id' => $seller->id]);
    }

    public function test_destroy_shop_requires_password(): void
    {
        $user = User::factory()->create();
        Seller::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('account.store.destroy'), [
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_toggle_auto_renew_enables_setting(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $user->id,
            'auto_renew' => false,
        ]);

        $response = $this->actingAs($user)->postJson(route('account.store.toggle-auto-renew'), [
            'auto_renew' => '1',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'auto_renew' => true,
            ]);
        $this->assertTrue($seller->fresh()->auto_renew);
    }

    public function test_toggle_auto_renew_disables_setting_when_value_missing(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $user->id,
            'auto_renew' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('account.store.toggle-auto-renew'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'auto_renew' => false,
            ]);
        $this->assertFalse($seller->fresh()->auto_renew);
    }

    public function test_toggle_auto_renew_returns_404_without_shop(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('account.store.toggle-auto-renew'), [
            'auto_renew' => '1',
        ]);

        $response->assertNotFound()
            ->assertJsonStructure(['error']);
    }
}
