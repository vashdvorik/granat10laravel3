<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OrderAnonymizationService
{
    public function eligibleQuery(): Builder
    {
        $cutoff = now()->subDays((int) config('privacy.order_retention_days', 365));

        return Order::query()
            ->whereNull('anonymized_at')
            ->where(function (Builder $query) use ($cutoff): void {
                $query
                    ->where('completed_at', '<=', $cutoff)
                    ->orWhere('cancelled_at', '<=', $cutoff);
            });
    }

    public function anonymize(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->anonymized_at !== null || ! $this->eligibleQuery()->whereKey($lockedOrder)->exists()) {
                return false;
            }

            $lockedOrder->forceFill([
                'customer_name' => 'Удалено',
                'customer_phone' => 'Удалено',
                'customer_phone_hash' => hash('sha256', 'anonymized-order-'.$lockedOrder->id),
                'delivery_address' => null,
                'customer_comment' => null,
                'source_ip_hash' => null,
                'access_token_hash' => hash('sha256', 'revoked-order-'.$lockedOrder->id),
                'anonymized_at' => now(),
            ])->save();

            return true;
        }, 3);
    }

    public function anonymizeExpired(?int $limit = null): int
    {
        $limit ??= (int) config('privacy.anonymization_batch_size', 200);
        $count = 0;

        $this->eligibleQuery()
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (Order $order) use (&$count): void {
                if ($this->anonymize($order)) {
                    $count++;
                }
            });

        return $count;
    }
}
