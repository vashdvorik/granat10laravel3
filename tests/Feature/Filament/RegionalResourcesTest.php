<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\RegionResource\Pages\CreateRegion;
use App\Filament\Resources\RegionResource\Pages\EditRegion;
use App\Filament\Resources\RegionResource\Pages\ListRegions;
use App\Filament\Resources\SettlementResource\Pages\CreateSettlement;
use App\Filament\Resources\SettlementResource\Pages\EditSettlement;
use App\Filament\Resources\SettlementResource\Pages\ListSettlements;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Seller;
use App\Models\Settlement;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegionalResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));
    }

    public function test_administrator_can_open_regional_resources(): void
    {
        $administrator = $this->createAdministrator();

        $this->actingAs($administrator)->get('/admin/regions')->assertOk();
        $this->actingAs($administrator)->get('/admin/settlements')->assertOk();
    }

    public function test_regular_user_cannot_open_regional_resources(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/regions')->assertForbidden();
        $this->actingAs($user)->get('/admin/settlements')->assertForbidden();
    }

    public function test_administrator_can_create_and_edit_region(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(CreateRegion::class)
            ->fillForm($this->regionData())
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $region = Region::query()->where('slug', 'test-region')->firstOrFail();

        Livewire::test(EditRegion::class, ['record' => $region->getRouteKey()])
            ->fillForm([...$this->regionData(), 'name' => 'Обновлённый регион'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame('Обновлённый регион', $region->refresh()->name);
        $this->assertSame('Регион для SEO', $region->meta_title);
    }

    public function test_administrator_can_create_and_edit_settlement(): void
    {
        $this->actingAs($this->createAdministrator());
        $region = Region::factory()->create();

        Livewire::test(CreateSettlement::class)
            ->fillForm($this->settlementData($region))
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $settlement = Settlement::query()->where('slug', 'test-city')->firstOrFail();

        Livewire::test(EditSettlement::class, ['record' => $settlement->getRouteKey()])
            ->fillForm([...$this->settlementData($region), 'name' => 'Обновлённый город'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame('Обновлённый город', $settlement->refresh()->name);
        $this->assertSame($region->id, $settlement->region_id);
    }

    public function test_delete_actions_protect_related_records(): void
    {
        $this->actingAs($this->createAdministrator());
        $region = Region::factory()->create();
        $settlement = Settlement::factory()->for($region)->create();
        Seller::factory()->for($settlement)->create();

        Livewire::test(ListRegions::class)
            ->callTableAction('deleteRegion', $region)
            ->assertTableActionHalted('deleteRegion');

        Livewire::test(ListSettlements::class)
            ->callTableAction('deleteSettlement', $settlement)
            ->assertTableActionHalted('deleteSettlement');

        $this->assertDatabaseHas('regions', ['id' => $region->id]);
        $this->assertDatabaseHas('settlements', ['id' => $settlement->id]);
    }

    public function test_empty_regional_records_can_be_deleted(): void
    {
        $this->actingAs($this->createAdministrator());
        $region = Region::factory()->create();
        $settlement = Settlement::factory()->for($region)->create();

        Livewire::test(ListSettlements::class)
            ->callTableAction('deleteSettlement', $settlement)
            ->assertNotified();

        Livewire::test(ListRegions::class)
            ->callTableAction('deleteRegion', $region)
            ->assertNotified();

        $this->assertDatabaseMissing('settlements', ['id' => $settlement->id]);
        $this->assertDatabaseMissing('regions', ['id' => $region->id]);
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

    private function regionData(): array
    {
        return [
            'name' => 'Тестовый регион',
            'type' => 'Район',
            'slug' => 'test-region',
            'sort_order' => 10,
            'is_active' => true,
            'meta_title' => 'Регион для SEO',
            'meta_description' => 'Описание региона',
        ];
    }

    private function settlementData(Region $region): array
    {
        return [
            'region_id' => $region->id,
            'name' => 'Тестовый город',
            'type' => 'город',
            'zip' => 'MD-0000',
            'slug' => 'test-city',
            'sort_order' => 10,
            'is_active' => true,
            'meta_title' => 'Город для SEO',
            'meta_description' => 'Описание города',
        ];
    }
}
