<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasPermission('browse_admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->seller !== null;
    }

    public function view(User $user, Order $order): bool
    {
        return $user->seller?->id === $order->seller_id;
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $this->view($user, $order) && ! $order->isFinal();
    }
}
