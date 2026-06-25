<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductModerationDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Product $product
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'status' => $this->product->moderation_status->value,
            'status_label' => $this->product->display_status,
            'comment' => $this->product->moderation_summary,
            'url' => route('account.store.products.show', $this->product),
        ];
    }
}
