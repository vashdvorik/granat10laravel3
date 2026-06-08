<?php

namespace Tests\Unit;

use App\Rules\SafeEmail;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SafeEmailTest extends TestCase
{
    public function test_regular_email_is_allowed(): void
    {
        $validator = Validator::make(
            ['email' => 'user@example.com'],
            ['email' => ['email', new SafeEmail]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_line_breaks_are_rejected(): void
    {
        foreach (["user@example.com\rBcc: attacker@example.com", "user@example.com\nBcc: attacker@example.com"] as $email) {
            $validator = Validator::make(
                ['email' => $email],
                ['email' => [new SafeEmail]]
            );

            $this->assertTrue($validator->fails());
        }
    }
}
