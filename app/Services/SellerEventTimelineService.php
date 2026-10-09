<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class SellerEventTimelineService
{
    /**
     * @return array<int, array{
     *     date: \Illuminate\Support\Carbon,
     *     title: string,
     *     description: string|null,
     *     tone: string,
     *     url: string|null,
     *     action: string|null
     * }>
     */
    public function latestForSeller(Seller $seller, int $limit = 20): array
    {
        $limit = max(1, min($limit, 50));

        return collect()
            ->merge($this->activityEvents($seller, $limit))
            ->merge($this->orderEvents($seller, $limit))
            ->sortByDesc('date')
            ->take($limit)
            ->values()
            ->all();
    }

    private function activityEvents(Seller $seller, int $limit): Collection
    {
        $productIds = $seller->products()->pluck('id')->all();

        $activities = Activity::query()
            ->where(function ($query) use ($seller, $productIds) {
                $query
                    ->where(function ($sellerQuery) use ($seller) {
                        $sellerQuery
                            ->where('subject_type', Seller::class)
                            ->where('subject_id', $seller->id);
                    })
                    ->orWhere(function ($userQuery) use ($seller) {
                        $userQuery
                            ->where('causer_type', User::class)
                            ->where('causer_id', $seller->user_id)
                            ->whereIn('log_name', ['sellers', 'products']);
                    });

                if ($productIds !== []) {
                    $query->orWhere(function ($productQuery) use ($productIds) {
                        $productQuery
                            ->where('subject_type', Product::class)
                            ->whereIn('subject_id', $productIds);
                    });
                }
            })
            ->latest()
            ->limit($limit * 2)
            ->get();

        return $activities
            ->map(fn (Activity $activity): array => $this->mapActivity($activity))
            ->filter(fn (array $event): bool => $event['title'] !== '')
            ->values();
    }

    private function orderEvents(Seller $seller, int $limit): Collection
    {
        return OrderStatusHistory::query()
            ->whereHas('order', fn ($query) => $query->where('seller_id', $seller->id))
            ->with('order')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (OrderStatusHistory $history): array => $this->mapOrderHistory($history));
    }

    private function mapActivity(Activity $activity): array
    {
        $properties = $activity->properties;
        $reason = $properties->get('reason');

        return [
            'date' => $activity->created_at,
            'title' => (string) $activity->description,
            'description' => $this->activityDescription($activity, is_string($reason) ? $reason : null),
            'tone' => $this->activityTone($activity),
            'url' => $this->activityUrl($activity),
            'action' => $this->activityAction($activity),
        ];
    }

    private function activityDescription(Activity $activity, ?string $reason): ?string
    {
        if ($activity->subject_type === Product::class) {
            return 'Изменение товара может влиять на его публикацию в каталоге.';
        }

        if ($activity->subject_type === Seller::class || $activity->log_name === 'sellers') {
            return match ($activity->properties->get('visibility')) {
                'hidden' => $reason !== null ? 'Причина: '.$reason : 'Магазин не показывается покупателям.',
                'shown' => 'Магазин и его товары снова видны покупателям.',
                default => 'Обновлены данные магазина, которые видят покупатели.',
            };
        }

        return null;
    }

    private function activityTone(Activity $activity): string
    {
        if ($activity->subject_type === Product::class) {
            return 'info';
        }

        return match ($activity->properties->get('visibility')) {
            'hidden' => 'danger',
            'shown' => 'success',
            default => 'neutral',
        };
    }

    private function activityUrl(Activity $activity): ?string
    {
        if ($activity->subject_type === Product::class && $activity->subject_id) {
            return route('account.store.products.show', $activity->subject_id);
        }

        if ($activity->subject_type === Seller::class || $activity->log_name === 'sellers') {
            return route('account.index');
        }

        return null;
    }

    private function activityAction(Activity $activity): ?string
    {
        if ($activity->subject_type === Product::class) {
            return 'Открыть товар';
        }

        if ($activity->subject_type === Seller::class || $activity->log_name === 'sellers') {
            return 'Открыть магазин';
        }

        return null;
    }

    private function mapOrderHistory(OrderStatusHistory $history): array
    {
        $order = $history->order;
        $status = $history->to_status;

        return [
            'date' => $history->created_at,
            'title' => 'Статус заказа '.$order->number.' изменён',
            'description' => trim(($status?->label() ?? '').($history->comment ? ' · '.$history->comment : '')),
            'tone' => $this->orderTone($status),
            'url' => route('account.store.orders.show', $order),
            'action' => 'Открыть заказ',
        ];
    }

    private function orderTone(?OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Completed => 'success',
            OrderStatus::Cancelled => 'danger',
            OrderStatus::Accepted,
            OrderStatus::Processing,
            OrderStatus::ReadyForPickup,
            OrderStatus::OutForDelivery => 'info',
            default => 'neutral',
        };
    }
}
