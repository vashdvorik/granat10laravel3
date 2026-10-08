<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Crypt;

/** Поля, которые браузер отправляет вместе с формами регистрации и восстановления пароля (см. ProtectAuthForms). */
trait FormProtectionFields
{
    /** @return array<string, string> */
    protected function formProtection(int $ageSeconds = 30): array
    {
        return [
            'contact_url' => '',
            'form_token' => Crypt::encryptString((string) (time() - $ageSeconds)),
        ];
    }
}
