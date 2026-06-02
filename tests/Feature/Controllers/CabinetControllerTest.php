<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CabinetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_cabinet(): void
    {
        $response = $this->get(route('cabinet.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_cabinet(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('cabinet.index'));

        $response->assertStatus(200);
        $response->assertViewIs('cabinet.index');
        $response->assertViewHas('user');
    }

    public function test_cabinet_displays_seller_info_if_exists(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Shop',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('cabinet.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Shop');
    }

    public function test_can_view_stats_page(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->get(route('cabinet.stats'));

        $response->assertStatus(200);
        $response->assertViewIs('cabinet.stats');
        $response->assertViewHas('stats');
    }

    public function test_stats_page_requires_seller_account(): void
    {
        $user = User::factory()->create();
        // У пользователя нет магазина

        $this->actingAs($user);

        $response = $this->get(route('cabinet.stats'));

        $response->assertRedirect(route('cabinet.index'));
        $response->assertSessionHas('error');
    }

    public function test_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $this->actingAs($user);

        $response = $this->put(route('cabinet.profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '+1234567890',
        ]);

        $response->assertRedirect(route('cabinet.index'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('new@example.com', $user->email);
    }

    public function test_cannot_update_profile_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->put(route('cabinet.profile.update'), [
            'name' => '',
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors(['name', 'email']);
    }

    public function test_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user);

        $response = $this->put(route('cabinet.password.update'), [
            'current_password' => 'old-password',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertRedirect(route('cabinet.index'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $user->password));
    }

    public function test_cannot_change_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user);

        $response = $this->put(route('cabinet.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    public function test_can_create_shop(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('cabinet.shop.store'), [
            'name' => 'My New Shop',
            'slug' => 'my-new-shop',
            'phone' => '+1234567890',
            'description' => 'Shop description',
        ]);

        $response->assertRedirect(route('cabinet.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sellers', [
            'user_id' => $user->id,
            'name' => 'My New Shop',
            'slug' => 'my-new-shop',
        ]);
    }

    public function test_cannot_create_shop_if_already_exists(): void
    {
        $user = User::factory()->create();
        Seller::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->post(route('cabinet.shop.store'), [
            'name' => 'Another Shop',
            'slug' => 'another-shop',
            'phone' => '+1234567890',
        ]);

        $response->assertRedirect(route('cabinet.index'));
        $response->assertSessionHas('error');
    }

    public function test_can_update_shop(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old Shop Name',
        ]);

        $this->actingAs($user);

        $response = $this->put(route('cabinet.shop.update'), [
            'name' => 'New Shop Name',
            'phone' => '+1234567890',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('cabinet.index'));
        $response->assertSessionHas('success');

        $seller->refresh();
        $this->assertEquals('New Shop Name', $seller->name);
    }

    public function test_can_delete_shop_with_password_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->delete(route('cabinet.shop.destroy'), [
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('cabinet.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('sellers', [
            'id' => $seller->id,
        ]);
    }

    public function test_cannot_delete_shop_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->delete(route('cabinet.shop.destroy'), [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHas('error');

        $this->assertDatabaseHas('sellers', [
            'id' => $seller->id,
        ]);
    }

    public function test_can_activate_package(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create([
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('cabinet.package.activate', $package->id));

        $response->assertRedirect(route('cabinet.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('seller_packages', [
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
        ]);
    }

    public function test_cannot_activate_package_without_shop(): void
    {
        $user = User::factory()->create();
        // У пользователя нет магазина

        $package = Package::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('cabinet.package.activate', $package->id));

        $response->assertRedirect(route('cabinet.index'));
        $response->assertSessionHas('error');
    }

    public function test_stats_show_correct_product_counts(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        // Создаем продукты
        Product::factory()->count(5)->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        Product::factory()->count(3)->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => false,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('cabinet.stats'));

        $response->assertStatus(200);
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_products'] === 8
                && $stats['active_products'] === 5;
        });
    }
}
