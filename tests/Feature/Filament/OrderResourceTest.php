<?php

namespace Tests\Feature\Filament;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\OrderResource\RelationManagers\StatusHistoryRelationManager;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Seller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));
    }

    public function test_administrator_can_open_order_resource_and_regular_user_cannot(): void
    {
        $administrator = $this->createAdministrator();

        $this->actingAs($administrator)
            ->get('/admin/orders')
            ->assertOk();

        $this->actingAs(User::factory()->create())
            ->get('/admin/orders')
            ->assertForbidden();
    }

    public function test_order_table_supports_status_and_seller_filters(): void
    {
        $administrator = $this->createAdministrator();
        $firstSeller = Seller::factory()->create();
        $secondSeller = Seller::factory()->create();
        $firstOrder = Order::factory()->create([
            'seller_id' => $firstSeller->id,
            'status' => OrderStatus::New,
        ]);
        $secondOrder = Order::factory()->create([
            'seller_id' => $secondSeller->id,
            'status' => OrderStatus::Accepted,
        ]);

        $this->actingAs($administrator);

        Livewire::test(ListOrders::class)
            ->filterTable('status', OrderStatus::New->value)
            ->assertCanSeeTableRecords([$firstOrder])
            ->assertCanNotSeeTableRecords([$secondOrder])
            ->resetTableFilters()
            ->filterTable('seller_id', $secondSeller->id)
            ->assertCanSeeTableRecords([$secondOrder])
            ->assertCanNotSeeTableRecords([$firstOrder]);
    }

    public function test_administrator_changes_status_through_domain_service(): void
    {
        $administrator = $this->createAdministrator();
        $order = Order::factory()->create(['status' => OrderStatus::New]);

        $this->actingAs($administrator);

        Livewire::test(ListOrders::class)
            ->callTableAction('changeStatus', $order, [
                'status' => OrderStatus::Accepted->value,
                'comment' => 'Проверено администратором.',
            ])
            ->assertNotified();

        $this->assertSame(OrderStatus::Accepted, $order->fresh()->status);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'changed_by_user_id' => $administrator->id,
            'to_status' => OrderStatus::Accepted->value,
        ]);
    }

    public function test_order_view_is_read_only_and_contains_history_manager(): void
    {
        $administrator = $this->createAdministrator();
        $order = Order::factory()->create();

        $this->actingAs($administrator);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->assertFormFieldIsDisabled('number')
            ->assertFormFieldIsDisabled('customer_name')
            ->assertFormFieldDoesNotExist('access_token_hash')
            ->assertFormFieldDoesNotExist('idempotency_key_hash');

        $this->get("/admin/orders/{$order->id}")->assertOk();
        $this->assertContains(StatusHistoryRelationManager::class, OrderResource::getRelations());
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
