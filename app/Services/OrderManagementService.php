<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderManagementService
{
    public function transition(
        Order $order,
        OrderStatus $toStatus,
        User $actor,
        ?string $comment = null,
    ): Order {
        return DB::transaction(function () use ($order, $toStatus, $actor, $comment): Order {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $fromStatus = $lockedOrder->status;

            if ($fromStatus === $toStatus) {
                return $lockedOrder;
            }

            $now = now();
            $attributes = [
                'status' => $toStatus,
                'status_changed_at' => $now,
                'completed_at' => null,
                'cancelled_at' => null,
            ];

            if ($toStatus === OrderStatus::Completed) {
                $attributes['completed_at'] = $now;
            }

            if ($toStatus === OrderStatus::Cancelled) {
                $attributes['cancelled_at'] = $now;
            }

            $lockedOrder->forceFill($attributes)->save();
            $lockedOrder->statusHistory()->create([
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by_user_id' => $actor->id,
                'comment' => filled($comment) ? trim((string) $comment) : null,
            ]);

            OrderStatusChanged::dispatch($lockedOrder->id, $actor->id);

            return $lockedOrder->refresh();
        }, 3);
    }
}
