<?php

namespace Tests\Feature;

use App\Models\Seller;
use App\Models\User;
use App\Services\AccountSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AccountSecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    private AccountSecurityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AccountSecurityService();
    }

    public function test_update_password_changes_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123'),
        ]);

        $this->service->updatePassword($user, 'OldPassword123', 'NewPassword456');

        $this->assertTrue(Hash::check('NewPassword456', $user->fresh()->password));
    }

    public function test_update_password_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123'),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Текущий пароль неверный');

        try {
            $this->service->updatePassword($user, 'WrongPassword', 'NewPassword456');
        } finally {
            $this->assertTrue(Hash::check('OldPassword123', $user->fresh()->password));
        }
    }

    public function test_delete_shop_removes_seller(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123'),
        ]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $sellerName = $this->service->deleteShop($user->fresh('seller'), 'Password123');

        $this->assertSame($seller->name, $sellerName);
        $this->assertDatabaseMissing('sellers', ['id' => $seller->id]);
    }

    public function test_delete_shop_rejects_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123'),
        ]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Неверный пароль');

        try {
            $this->service->deleteShop($user->fresh('seller'), 'WrongPassword');
        } finally {
            $this->assertDatabaseHas('sellers', ['id' => $seller->id]);
        }
    }
}
