<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    public function test_create_shop_form_returns_200_when_no_shop(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('account.store.create'));

        $response->assertStatus(200);
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
            'town'        => 'Москва',
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
        ]);

        $seller = Seller::where('user_id', $user->id)->first();

        $this->assertNotNull($seller->alias);
        $this->assertNotEmpty($seller->alias);
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
}
