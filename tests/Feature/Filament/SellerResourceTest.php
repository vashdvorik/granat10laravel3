<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SellerResource;
use App\Filament\Resources\SellerResource\Pages\CreateSeller;
use App\Filament\Resources\SellerResource\Pages\EditSeller;
use App\Filament\Resources\SellerResource\Pages\ListSellers;
use App\Models\Package;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SellerResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));
    }

    public function test_administrator_can_open_seller_resource(): void
    {
        $administrator = $this->createAdministrator();

        $this->actingAs($administrator)
            ->get('/admin/sellers')
            ->assertOk();
    }

    public function test_regular_user_cannot_open_seller_resource(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/sellers')
            ->assertForbidden();
    }

    public function test_seller_list_contains_active_products_and_package(): void
    {
        $administrator = $this->createAdministrator();
        $seller = Seller::factory()->create();
        Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);
        Product::factory()->inactive()->create([
            'seller_id' => $seller->id,
        ]);
        $package = Package::factory()->create(['name' => 'Профессиональный']);
        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => now()->addMonth(),
        ]);

        $this->actingAs($administrator);

        Livewire::test(ListSellers::class)
            ->assertCanSeeTableRecords([$seller])
            ->assertTableColumnStateSet('active_products_count', 1, $seller->id)
            ->assertTableColumnStateSet('activeSellerPackage.package.name', 'Профессиональный', $seller->id)
            ->assertTableActionHasUrl('publicPage', route('seller', $seller->alias), $seller)
            ->assertTableActionHasUrl('products', SellerResource::getUrl('edit', [
                'record' => $seller,
                'activeRelationManager' => 0,
            ]), $seller);
    }

    public function test_administrator_can_create_seller_with_base_package(): void
    {
        $administrator = $this->createAdministrator();
        $owner = User::factory()->create();
        $basePackage = Package::factory()->create([
            'id' => 1,
            'name' => 'Базовый',
        ]);

        $this->actingAs($administrator);

        Livewire::test(CreateSeller::class)
            ->fillForm([
                'user_id' => $owner->id,
                'name' => 'Новый магазин',
                'phone' => '+37360000000',
                'town' => 'Кишинёв',
                'link' => 'https://example.test',
                'telegram' => '@new_shop',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $seller = Seller::query()->where('user_id', $owner->id)->firstOrFail();

        $this->assertSame('Новый магазин', $seller->name);
        $this->assertNotEmpty($seller->alias);
        $this->assertSame('https://example.test', $seller->link);
        $this->assertDatabaseHas('seller_packages', [
            'seller_id' => $seller->id,
            'package_id' => $basePackage->id,
            'is_active' => true,
        ]);
    }

    public function test_edit_keeps_owner_and_product_count_unchanged(): void
    {
        $administrator = $this->createAdministrator();
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $owner->id,
            'product_count' => 7,
        ]);

        $this->actingAs($administrator);

        Livewire::test(EditSeller::class, ['record' => $seller->getRouteKey()])
            ->assertFormFieldIsDisabled('user_id')
            ->assertFormFieldIsDisabled('alias')
            ->assertFormFieldDoesNotExist('product_count')
            ->fillForm([
                'user_id' => $otherUser->id,
                'name' => 'Обновлённый магазин',
                'phone' => '+37361111111',
                'town' => 'Бельцы',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $seller->refresh();

        $this->assertSame($owner->id, $seller->user_id);
        $this->assertSame(7, $seller->product_count);
        $this->assertSame('Обновлённый магазин', $seller->name);
    }

    public function test_auto_renew_action_uses_settings_service(): void
    {
        $administrator = $this->createAdministrator();
        $seller = Seller::factory()->create(['auto_renew' => false]);

        $this->actingAs($administrator);

        Livewire::test(ListSellers::class)
            ->callTableAction('toggleAutoRenew', $seller)
            ->assertNotified();

        $this->assertTrue($seller->refresh()->auto_renew);
    }

    public function test_edit_page_displays_products_and_package_history_relations(): void
    {
        $administrator = $this->createAdministrator();
        $seller = Seller::factory()->create();

        $this->actingAs($administrator)
            ->get("/admin/sellers/{$seller->id}/edit")
            ->assertOk()
            ->assertSee('Товары')
            ->assertSee('История тарифов');
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
