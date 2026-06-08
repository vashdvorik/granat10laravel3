<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PackageResource\Pages\CreatePackage;
use App\Filament\Resources\PackageResource\Pages\EditPackage;
use App\Filament\Resources\PackageResource\Pages\ListPackages;
use App\Filament\Resources\SellerPackageResource\Pages\ListSellerPackages;
use App\Models\Package;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PackageResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));
    }

    public function test_administrator_can_open_package_and_subscription_resources(): void
    {
        $administrator = $this->createAdministrator();

        $this->actingAs($administrator)->get('/admin/packages')->assertOk();
        $this->actingAs($administrator)->get('/admin/seller-packages')->assertOk();
    }

    public function test_regular_user_cannot_open_package_or_subscription_resources(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/packages')->assertForbidden();
        $this->actingAs($user)->get('/admin/seller-packages')->assertForbidden();
    }

    public function test_administrator_can_create_package_with_explicit_limits(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(CreatePackage::class)
            ->fillForm($this->packageData())
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $package = Package::query()->where('name', 'Тестовый тариф')->firstOrFail();

        $this->assertSame('125.50', $package->price);
        $this->assertNull($package->duration_days);
        $this->assertSame(0, $package->vip_products_limit);
        $this->assertNull($package->active_products_limit);
        $this->assertTrue($package->has_order_system);
    }

    public function test_editing_limits_does_not_deactivate_existing_subscription(): void
    {
        $administrator = $this->createAdministrator();
        $package = Package::factory()->withLimits(100, 10)->create();
        $subscription = SellerPackage::factory()->create([
            'package_id' => $package->id,
            'is_active' => true,
        ]);
        $this->actingAs($administrator);

        Livewire::test(EditPackage::class, ['record' => $package->getRouteKey()])
            ->fillForm([
                ...$this->packageData(),
                'name' => $package->name,
                'active_products_limit' => 1,
                'vip_products_limit' => 0,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertTrue($subscription->fresh()->is_active);
        $this->assertSame(1, $package->fresh()->active_products_limit);
    }

    public function test_package_list_displays_rup_and_unlimited_semantics(): void
    {
        $administrator = $this->createAdministrator();
        Package::factory()->create([
            'name' => 'Безлимит',
            'price' => 99,
            'duration_days' => null,
            'active_products_limit' => null,
            'vip_products_limit' => 0,
        ]);
        $this->actingAs($administrator);

        Livewire::test(ListPackages::class)
            ->assertSee('99.00 RUP')
            ->assertSee('Бессрочно')
            ->assertSee('Без ограничений')
            ->assertSee('Запрещено');
    }

    public function test_delete_action_blocks_used_package_and_deletes_unused_package(): void
    {
        $administrator = $this->createAdministrator();
        $usedPackage = Package::factory()->create();
        SellerPackage::factory()->create(['package_id' => $usedPackage->id]);
        $unusedPackage = Package::factory()->create();
        $this->actingAs($administrator);

        Livewire::test(ListPackages::class)
            ->callTableAction('deletePackage', $usedPackage)
            ->assertTableActionHalted('deletePackage');

        Livewire::test(ListPackages::class)
            ->callTableAction('deletePackage', $unusedPackage)
            ->assertNotified();

        $this->assertDatabaseHas('packages', ['id' => $usedPackage->id]);
        $this->assertDatabaseMissing('packages', ['id' => $unusedPackage->id]);
    }

    public function test_subscription_actions_assign_renew_and_deactivate_through_service(): void
    {
        $administrator = $this->createAdministrator();
        $owner = User::factory()->create(['balance' => 500]);
        $seller = Seller::factory()->create(['user_id' => $owner->id]);
        $package = Package::factory()->create([
            'price' => 100,
            'duration_days' => 30,
        ]);
        $this->actingAs($administrator);

        Livewire::test(ListSellerPackages::class)
            ->callTableAction('assignPackage', null, [
                'seller_id' => $seller->id,
                'package_id' => $package->id,
                'charge_balance' => true,
                'reason' => 'Назначение из Filament',
            ])
            ->assertNotified();

        $subscription = SellerPackage::query()->firstOrFail();
        $this->assertSame(400.0, (float) $owner->fresh()->balance);

        Livewire::test(ListSellerPackages::class)
            ->callTableAction('renewPackage', $subscription, [
                'charge_balance' => false,
                'reason' => 'Бесплатное продление',
            ])
            ->assertNotified();

        Livewire::test(ListSellerPackages::class)
            ->callTableAction('deactivatePackage', $subscription->fresh(), [
                'reason' => 'Остановка подписки',
            ])
            ->assertNotified();

        $this->assertFalse($subscription->fresh()->is_active);
    }

    private function packageData(): array
    {
        return [
            'name' => 'Тестовый тариф',
            'price' => '125.50',
            'duration_days' => null,
            'active_products_limit' => null,
            'vip_products_limit' => 0,
            'has_website_link' => true,
            'has_social_links' => false,
            'has_order_system' => true,
            'is_active' => true,
        ];
    }

    private function createAdministrator(): User
    {
        $role = Role::query()->create([
            'name' => 'admin-'.uniqid(),
            'display_name' => 'Администратор',
        ]);
        $permission = Permission::query()->create([
            'key' => 'browse_admin',
            'table_name' => null,
        ]);
        $role->permissions()->attach($permission);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
