<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userService = new UserService;
    }

    public function test_can_update_profile(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $updated = $this->userService->updateProfile($user, [
            'name' => 'New Name',
            'email' => 'newemail@example.com',
        ]);

        $this->assertEquals('New Name', $updated->name);
        $this->assertEquals('newemail@example.com', $updated->email);
    }

    public function test_can_change_password(): void
    {
        $user = User::factory()->create();
        $newPassword = 'NewSecurePassword123';

        $this->userService->changePassword($user, $newPassword);

        $user->refresh();

        $this->assertTrue($this->userService->checkPassword($user, $newPassword));
    }

    public function test_check_password_returns_true_for_correct_password(): void
    {
        $password = 'TestPassword123';
        $user = User::factory()->create([
            'password' => \Hash::make($password),
        ]);

        $result = $this->userService->checkPassword($user, $password);

        $this->assertTrue($result);
    }

    public function test_check_password_returns_false_for_incorrect_password(): void
    {
        $user = User::factory()->create([
            'password' => \Hash::make('CorrectPassword'),
        ]);

        $result = $this->userService->checkPassword($user, 'WrongPassword');

        $this->assertFalse($result);
    }

    public function test_is_email_unique_returns_true_for_unique_email(): void
    {
        $result = $this->userService->isEmailUnique('unique@example.com');

        $this->assertTrue($result);
    }

    public function test_is_email_unique_returns_false_for_existing_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $result = $this->userService->isEmailUnique('existing@example.com');

        $this->assertFalse($result);
    }

    public function test_is_email_unique_excludes_current_user(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Проверяем что email уникален если исключаем текущего пользователя
        $result = $this->userService->isEmailUnique('test@example.com', $user->id);

        $this->assertTrue($result);
    }
}
