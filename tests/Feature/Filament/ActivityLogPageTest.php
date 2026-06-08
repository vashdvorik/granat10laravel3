<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Seller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));
    }

    public function test_administrator_can_open_activity_log_page(): void
    {
        $administrator = $this->createAdministrator();

        $this->actingAs($administrator)
            ->get('/admin/activity-log')
            ->assertOk()
            ->assertSee('Журнал действий');
    }

    public function test_regular_user_cannot_open_activity_log_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/activity-log')
            ->assertForbidden();
    }

    public function test_table_supports_description_search_and_log_name_filter(): void
    {
        $administrator = $this->createAdministrator();
        Activity::query()->delete();
        $login = $this->activity('auth', 'Успешный вход администратора', $administrator);
        $package = $this->activity('packages', 'Назначен тариф магазину', $administrator);

        $this->actingAs($administrator);

        Livewire::test(ActivityLog::class)
            ->assertCanSeeTableRecords([$login, $package])
            ->searchTable('тариф')
            ->assertCanSeeTableRecords([$package])
            ->assertCanNotSeeTableRecords([$login])
            ->searchTable()
            ->filterTable('log_name', 'auth')
            ->assertCanSeeTableRecords([$login])
            ->assertCanNotSeeTableRecords([$package]);
    }

    public function test_table_filters_by_causer_and_seller_name(): void
    {
        $administrator = $this->createAdministrator();
        $otherUser = User::factory()->create();
        $sellerOwner = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $sellerOwner->id,
            'name' => 'Магазин Север',
        ]);
        Activity::query()->delete();

        $adminActivity = $this->activity('auth', 'Вход администратора', $administrator);
        $sellerActivity = $this->activity(
            'sellers',
            'Магазин обновлён',
            $sellerOwner,
            $seller,
            ['seller_name' => $seller->name],
        );
        $otherActivity = $this->activity('auth', 'Другое действие', $otherUser);

        $this->actingAs($administrator);

        Livewire::test(ActivityLog::class)
            ->filterTable('causer', ['value' => $administrator->id])
            ->assertCanSeeTableRecords([$adminActivity])
            ->assertCanNotSeeTableRecords([$sellerActivity, $otherActivity])
            ->resetTableFilters()
            ->filterTable('seller_name', ['value' => 'Север'])
            ->assertCanSeeTableRecords([$sellerActivity])
            ->assertCanNotSeeTableRecords([$adminActivity, $otherActivity]);
    }

    public function test_details_action_displays_properties_ip_and_metadata(): void
    {
        $administrator = $this->createAdministrator();
        Activity::query()->delete();
        $activity = $this->activity(
            'packages',
            'Административное назначение тарифа',
            $administrator,
            properties: [
                'ip' => '192.0.2.10',
                'reason' => 'Компенсация',
                'package_name' => 'Бизнес',
            ],
        );

        $this->actingAs($administrator);

        Livewire::test(ActivityLog::class)
            ->mountTableAction('viewDetails', $activity)
            ->assertSee('192.0.2.10')
            ->assertSee('Компенсация')
            ->assertSee('Бизнес');
    }

    public function test_table_uses_thirty_records_per_page(): void
    {
        $administrator = $this->createAdministrator();
        Activity::query()->delete();

        foreach (range(1, 35) as $index) {
            $this->activity('system', "Событие {$index}", $administrator);
        }

        $this->actingAs($administrator);

        Livewire::test(ActivityLog::class)
            ->assertSet('tableRecordsPerPage', 30)
            ->assertCountTableRecords(35);
    }

    private function activity(
        string $logName,
        string $description,
        User $causer,
        mixed $subject = null,
        array $properties = []
    ): Activity {
        $logger = activity($logName)
            ->causedBy($causer)
            ->withProperties($properties);

        if ($subject) {
            $logger->performedOn($subject);
        }

        $logger->log($description);

        return Activity::query()->latest('id')->firstOrFail();
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
