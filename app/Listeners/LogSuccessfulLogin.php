<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'guard' => $event->guard,
                'ip'    => request()->ip(),
            ])
            ->log('Вход в систему');
    }
}
