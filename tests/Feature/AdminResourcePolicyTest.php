<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AdminResourcePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_manage_filament_resources(): void
    {
        $administrator = $this->createAdministrator();
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();
        $package = Package::factory()->create();
        $sellerPackage = SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
        ]);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
        ]);

        $this->assertPolicyAbilities($administrator, User::class, $user, [
            'viewAny',
            'view',
            'create',
            'update',
            'adjustBalance',
        ]);
        $this->assertPolicyAbilities($administrator, Category::class, $category, [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            'reorder',
        ]);
        $this->assertPolicyAbilities($administrator, Package::class, $package, [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
        ]);
        $this->assertPolicyAbilities($administrator, SellerPackage::class, $sellerPackage, [
            'viewAny',
            'view',
            'create',
            'update',
        ]);
        $this->assertFalse(Gate::forUser($administrator)->allows('delete', $sellerPackage));
        $this->assertFalse(Gate::forUser($administrator)->allows('deleteAny', SellerPackage::class));
        $this->assertPolicyAbilities($administrator, Product::class, $product, [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            'deleteAny',
        ]);
        $this->assertPolicyAbilities($administrator, Seller::class, $seller, [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            'deleteAny',
        ]);
    }

    public function test_regular_user_cannot_manage_admin_resources(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $package = Package::factory()->create();

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', Category::class));
        $this->assertFalse(Gate::forUser($user)->allows('update', $category));
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', Package::class));
        $this->assertFalse(Gate::forUser($user)->allows('update', $package));
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', SellerPackage::class));
    }

    public function test_dangerous_bulk_deletes_are_disabled(): void
    {
        $administrator = $this->createAdministrator();

        $this->assertFalse(Gate::forUser($administrator)->allows('deleteAny', User::class));
        $this->assertFalse(Gate::forUser($administrator)->allows('deleteAny', Category::class));
        $this->assertFalse(Gate::forUser($administrator)->allows('deleteAny', Package::class));
    }

    public function test_administrator_cannot_delete_own_account(): void
    {
        $administrator = $this->createAdministrator();

        $this->assertFalse(Gate::forUser($administrator)->allows('delete', $administrator));
        $this->assertTrue(
            Gate::forUser($administrator)->allows('delete', User::factory()->create())
        );
    }

    public function test_balance_cannot_be_changed_by_regular_user_update(): void
    {
        $user = User::factory()->create(['balance' => 100]);

        $user->fill([
            'name' => 'Updated name',
            'balance' => 999,
        ])->save();

        $this->assertSame('Updated name', $user->refresh()->name);
        $this->assertSame(100.0, (float) $user->balance);
    }

    public function test_last_administrator_role_cannot_be_removed(): void
    {
        [$administrator, $adminRole] = $this->createAdministratorWithRole();

        $this->assertFalse(
            Gate::forUser($administrator)->allows('removeRole', [$administrator, $adminRole])
        );

        $secondAdministrator = User::factory()->create(['role_id' => $adminRole->id]);

        $this->assertTrue(
            Gate::forUser($administrator)->allows('removeRole', [$administrator, $adminRole])
        );
        $this->assertTrue(
            Gate::forUser($administrator)->allows('removeRole', [$secondAdministrator, $adminRole])
        );
    }

    private function assertPolicyAbilities(
        User $user,
        string $modelClass,
        object $model,
        array $abilities
    ): void {
        foreach ($abilities as $ability) {
            $arguments = in_array($ability, ['viewAny', 'create', 'deleteAny', 'reorder'], true)
                ? $modelClass
                : $model;

            $this->assertTrue(
                Gate::forUser($user)->allows($ability, $arguments),
                "Administrator should be allowed to {$ability} {$modelClass}."
            );
        }
    }

    private function createAdministrator(): User
    {
        return $this->createAdministratorWithRole()[0];
    }

    private function createAdministratorWithRole(): array
    {
        $role = Role::query()->create([
            'name' => 'admin-'.uniqid(),
            'display_name' => 'Administrator',
        ]);
        $permission = Permission::query()->create([
            'key' => 'browse_admin',
            'table_name' => null,
        ]);
        $role->permissions()->attach($permission);

        return [
            User::factory()->create(['role_id' => $role->id]),
            $role,
        ];
    }
}
