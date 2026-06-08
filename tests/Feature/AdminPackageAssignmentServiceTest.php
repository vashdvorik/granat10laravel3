<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use App\Services\AdminPackageAssignmentService;
use App\Services\AdminPackageService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AdminPackageAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_assignment_deactivates_previous_package_without_charging_balance(): void
    {
        $administrator = $this->createAdministrator();
        $owner = User::factory()->create(['balance' => 500]);
        $seller = Seller::factory()->create(['user_id' => $owner->id]);
        $oldPackage = Package::factory()->create();
        $newPackage = Package::factory()->create(['price' => 250]);
        $previous = SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $oldPackage->id,
            'is_active' => true,
        ]);

        $assigned = app(AdminPackageAssignmentService::class)->assign(
            $administrator,
            $seller,
            $newPackage,
            false,
            'Компенсация клиенту',
        );

        $this->assertFalse($previous->fresh()->is_active);
        $this->assertTrue($assigned->is_active);
        $this->assertSame($newPackage->id, $assigned->package_id);
        $this->assertSame(500.0, (float) $owner->fresh()->balance);

        $activity = Activity::query()->latest('id')->firstOrFail();
        $this->assertSame('Административное назначение тарифа', $activity->description);
        $this->assertSame('Компенсация клиенту', $activity->properties->get('reason'));
        $this->assertFalse($activity->properties->get('charge_balance'));
    }

    public function test_paid_assignment_charges_owner_balance(): void
    {
        $administrator = $this->createAdministrator();
        $owner = User::factory()->create(['balance' => 500]);
        $seller = Seller::factory()->create(['user_id' => $owner->id]);
        $package = Package::factory()->create(['price' => 125]);

        app(AdminPackageAssignmentService::class)->assign(
            $administrator,
            $seller,
            $package,
            true,
            'Ручная продажа тарифа',
        );

        $this->assertSame(375.0, (float) $owner->fresh()->balance);
    }

    public function test_assignment_requires_reason_and_keeps_existing_subscription(): void
    {
        $administrator = $this->createAdministrator();
        $owner = User::factory()->create(['balance' => 500]);
        $seller = Seller::factory()->create(['user_id' => $owner->id]);
        $oldPackage = Package::factory()->create();
        $newPackage = Package::factory()->create();
        $previous = SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $oldPackage->id,
            'is_active' => true,
        ]);

        try {
            app(AdminPackageAssignmentService::class)->assign(
                $administrator,
                $seller,
                $newPackage,
                false,
                ' ',
            );
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reason', $exception->errors());
        }

        $this->assertTrue($previous->fresh()->is_active);
        $this->assertSame(1, SellerPackage::query()->count());
    }

    public function test_renew_and_deactivate_are_logged(): void
    {
        $administrator = $this->createAdministrator();
        $owner = User::factory()->create(['balance' => 300]);
        $seller = Seller::factory()->create([
            'user_id' => $owner->id,
            'can_order' => true,
        ]);
        $package = Package::factory()->create([
            'price' => 100,
            'duration_days' => 30,
            'has_order_system' => true,
        ]);
        $subscription = SellerPackage::factory()->expired()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
        ]);
        $service = app(AdminPackageAssignmentService::class);

        $renewed = $service->renew(
            $administrator,
            $subscription,
            false,
            'Бесплатное продление',
        );

        $this->assertTrue($renewed->is_active);
        $this->assertTrue($renewed->end_date->isFuture());
        $this->assertSame(300.0, (float) $owner->fresh()->balance);

        $deactivated = $service->deactivate(
            $administrator,
            $renewed,
            'Запрос владельца',
        );

        $this->assertFalse($deactivated->is_active);
        $this->assertFalse($seller->fresh()->can_order);
        $this->assertSame(
            ['Административное продление тарифа', 'Административная деактивация тарифа'],
            Activity::query()
                ->where('log_name', 'packages')
                ->orderBy('id')
                ->pluck('description')
                ->all(),
        );
    }

    public function test_renewing_historical_subscription_deactivates_current_subscription(): void
    {
        $administrator = $this->createAdministrator();
        $owner = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $owner->id]);
        $historicalPackage = Package::factory()->create();
        $currentPackage = Package::factory()->create();
        $historical = SellerPackage::factory()->inactive()->create([
            'seller_id' => $seller->id,
            'package_id' => $historicalPackage->id,
        ]);
        $current = SellerPackage::factory()->active()->create([
            'seller_id' => $seller->id,
            'package_id' => $currentPackage->id,
        ]);

        app(AdminPackageAssignmentService::class)->renew(
            $administrator,
            $historical,
            false,
            'Возврат к прежнему тарифу',
        );

        $this->assertTrue($historical->fresh()->is_active);
        $this->assertFalse($current->fresh()->is_active);
        $this->assertSame(
            1,
            $seller->sellerPackages()->where('is_active', true)->count(),
        );
    }

    public function test_inactive_package_cannot_be_assigned(): void
    {
        $administrator = $this->createAdministrator();
        $owner = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $owner->id]);
        $package = Package::factory()->create(['is_active' => false]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('отключённый тариф');

        app(AdminPackageAssignmentService::class)->assign(
            $administrator,
            $seller,
            $package,
            false,
            'Тест',
        );
    }

    public function test_package_with_history_cannot_be_deleted_but_unused_package_can(): void
    {
        $administrator = $this->createAdministrator();
        $usedPackage = Package::factory()->create();
        SellerPackage::factory()->create(['package_id' => $usedPackage->id]);
        $unusedPackage = Package::factory()->create();
        $service = app(AdminPackageService::class);

        try {
            $service->delete($administrator, $usedPackage);
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('package', $exception->errors());
        }

        $service->delete($administrator, $unusedPackage);

        $this->assertDatabaseHas('packages', ['id' => $usedPackage->id]);
        $this->assertDatabaseMissing('packages', ['id' => $unusedPackage->id]);
    }

    public function test_regular_user_cannot_assign_package(): void
    {
        $this->expectException(AuthorizationException::class);

        $owner = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $owner->id]);

        app(AdminPackageAssignmentService::class)->assign(
            User::factory()->create(),
            $seller,
            Package::factory()->create(),
            false,
            'Нет прав',
        );
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
