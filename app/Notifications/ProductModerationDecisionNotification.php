<?php

namespace App\Notifications;

use App\Models\Product;
use App\Services\ProductStatusStoryFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Итог проверки товара (автоматической или ручной) в колокольчик кабинета.
 * Заголовок говорит, что случилось и что делать; замечание проверяющего приходит текстом.
 */
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
        $story = app(ProductStatusStoryFactory::class)->make($this->product);

        $title = match ($story->key) {
            'published' => 'Товар прошёл проверку и опубликован',
            'approved_hidden', 'no_images' => 'Товар прошёл проверку. '.($story->nextStep ?? ''),
            'changes_required' => 'Нужно исправить товар и отправить на проверку снова',
            'rejected' => 'Товар отклонён',
            default => $story->headline,
        };

        return [
            'type' => 'product_moderation',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'status' => $this->product->moderation_status->value,
            // status_label не задаём: колокольчик показал бы его второй строкой, дублируя заголовок
            'title' => trim($title),
            'comment' => $story->remark,
            'url' => route('account.store.products.show', $this->product),
        ];
    }
}
