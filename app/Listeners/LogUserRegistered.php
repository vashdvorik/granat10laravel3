<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;

class LogUserRegistered
{
    public function handle(Registered $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->withProperties([
                'email' => $event->user->email,
                'name'  => $event->user->name,
                'ip'    => request()->ip(),
            ])
            ->log('Регистрация нового пользователя');
    }
}
