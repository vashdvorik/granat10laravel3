<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SellerResource;
use App\Filament\Resources\SellerResource\Pages\CreateSeller;
use App\Filament\Resources\SellerResource\Pages\EditSeller;
use App\Filament\Resources\SellerResource\Pages\ListSellers;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Seller;
use App\Models\Settlement;
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

    public function test_seller_list_contains_active_products(): void
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

        $this->actingAs($administrator);

        Livewire::test(ListSellers::class)
            ->assertCanSeeTableRecords([$seller])
            ->assertTableColumnStateSet('active_products_count', 1, $seller->id)
            ->assertTableActionHasUrl('publicPage', route('seller', $seller->alias), $seller)
            ->assertTableActionHasUrl('products', SellerResource::getUrl('edit', [
                'record' => $seller,
                'activeRelationManager' => 0,
            ]), $seller);
    }

    public function test_administrator_can_create_seller(): void
    {
        $administrator = $this->createAdministrator();
        $owner = User::factory()->create();

        $this->actingAs($administrator);

        Livewire::test(CreateSeller::class)
            ->fillForm([
                'user_id' => $owner->id,
                'name' => 'Новый магазин',
                'phone' => '+37360000000',
                'settlement_id' => Settlement::factory()->create(['name' => 'Кишинёв'])->id,
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
                'settlement_id' => Settlement::factory()->create(['name' => 'Бельцы'])->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $seller->refresh();

        $this->assertSame($owner->id, $seller->user_id);
        $this->assertSame(7, $seller->product_count);
        $this->assertSame('Обновлённый магазин', $seller->name);
    }

    public function test_seller_list_has_no_package_or_auto_renew_controls(): void
    {
        $administrator = $this->createAdministrator();
        $seller = Seller::factory()->create();

        $this->actingAs($administrator);

        Livewire::test(ListSellers::class)
            ->assertCanSeeTableRecords([$seller])
            ->assertTableActionDoesNotExist('toggleAutoRenew');
    }

    public function test_edit_page_displays_products_relation_without_package_history(): void
    {
        $administrator = $this->createAdministrator();
        $seller = Seller::factory()->create();

        $this->actingAs($administrator)
            ->get("/admin/sellers/{$seller->id}/edit")
            ->assertOk()
            ->assertSee('Товары')
            ->assertDontSee('История тарифов');
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
