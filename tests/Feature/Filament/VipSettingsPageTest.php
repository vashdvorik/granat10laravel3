<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\VipSettings;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Seller;
use App\Models\User;
use App\Services\VipPricing;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class VipSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));
    }

    public function test_administrator_sees_vip_settings_with_default_price(): void
    {
        $this->actingAs($this->createAdministrator())
            ->get('/admin/vip-settings')
            ->assertOk()
            ->assertSee('Настройки VIP')
            ->assertSee('365.00 руб');
    }

    public function test_regular_user_cannot_open_vip_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/vip-settings')
            ->assertForbidden();
    }

    public function test_administrator_saves_price_per_day(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(VipSettings::class)
            ->assertFormSet(['price_per_day' => '1.00'])
            ->fillForm(['price_per_day' => '3.5'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame('3.50', app(VipPricing::class)->pricePerDay());
        $this->assertSame('24.50', app(VipPricing::class)->price(7));
    }

    public function test_negative_price_is_rejected(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(VipSettings::class)
            ->fillForm(['price_per_day' => '-1'])
            ->call('save')
            ->assertHasFormErrors(['price_per_day']);

        $this->assertSame('1.00', app(VipPricing::class)->pricePerDay());
    }

    public function test_administrator_can_set_and_remove_vip_on_product(): void
    {
        $administrator = $this->createAdministrator();
        $product = Product::factory()->create([
            'seller_id' => Seller::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'is_vip' => false,
        ]);

        $this->actingAs($administrator);

        $until = now()->addDays(10)->startOfMinute();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'is_active' => false, 'is_vip' => true,
                'vip_until' => $until->toDateTimeString(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertTrue($product->is_vip);
        $this->assertTrue($product->vip_until->equalTo($until));

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['is_active' => false, 'is_vip' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertFalse($product->is_vip);
        $this->assertNull($product->vip_until);
    }

    public function test_administrator_can_give_vip_without_end_date(): void
    {
        $administrator = $this->createAdministrator();
        $product = Product::factory()->create(['is_vip' => false]);

        $this->actingAs($administrator);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['is_active' => false, 'is_vip' => true, 'vip_until' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertTrue($product->is_vip);
        $this->assertNull($product->vip_until);
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
