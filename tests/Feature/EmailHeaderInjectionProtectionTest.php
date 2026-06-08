<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Tests\TestCase;

class EmailHeaderInjectionProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_rejects_email_with_line_breaks(): void
    {
        $this->expectException(ValidationException::class);

        app(CreateNewUser::class)->create([
            'name' => 'Unsafe user',
            'email' => "user@example.com\r\nBcc: attacker@example.com",
            'phone' => '12345678',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);
    }

    public function test_mail_routing_rejects_unsafe_stored_email(): void
    {
        $user = new User([
            'email' => "user@example.com\nBcc: attacker@example.com",
        ]);

        $this->expectException(InvalidArgumentException::class);

        $user->routeNotificationForMail();
    }
}
