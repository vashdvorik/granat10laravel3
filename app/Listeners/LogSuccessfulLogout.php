<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function handle(Logout $event): void
    {
        if ($event->user) {
            activity('auth')
                ->causedBy($event->user)
                ->withProperties(['ip' => request()->ip()])
                ->log('Выход из системы');
        }
    }
}
