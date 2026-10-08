<?php

namespace App\Application\Products;

use App\Data\ProductWriteOutcome;
use App\Enums\ProductModerationStatus;
use App\Models\Product;
use App\Services\ProductStatusStoryFactory;

/**
 * Сообщение продавцу после сохранения товара. Строится по результату, а не по нажатой кнопке:
 * «отправлено на проверку» говорится только тогда, когда товар действительно ушёл на проверку.
 */
final class ProductSaveMessage
{
    private const IN_REVIEW = [
        ProductModerationStatus::Pending,
        ProductModerationStatus::Processing,
        ProductModerationStatus::NeedsReview,
        ProductModerationStatus::Failed,
    ];

    public const SENT = 'Товар отправлен на проверку. Пока она идёт, покупатели его не видят. '
        .'Результат придёт в уведомления (колокольчик вверху) и появится на странице товара.';

    public static function forCreate(Product $product, bool $submitted): string
    {
        if (! $submitted) {
            return 'Черновик сохранён. Покупатели его не видят. Когда карточка готова, откройте товар и нажмите «Отправить на проверку».';
        }

        return self::afterSubmit($product);
    }

    public static function forUpdate(ProductWriteOutcome $outcome): string
    {
        $product = $outcome->product;
        $status = $product->moderation_status;

        if ($outcome->submitted) {
            return self::afterSubmit($product);
        }

        if ($status === ProductModerationStatus::Approved) {
            if ($product->is_active) {
                return $outcome->wasPublic
                    ? 'Изменения сохранены и уже видны покупателям.'
                    : 'Товар прошёл проверку и опубликован.';
            }

            return 'Изменения сохранены. Товар одобрен, но пока не опубликован: нажмите «Опубликовать», чтобы он появился в каталоге.';
        }

        if ($status === ProductModerationStatus::Draft) {
            if ($outcome->statusBefore === ProductModerationStatus::Approved) {
                return 'Изменения сохранены, но товар скрыт из каталога и стал черновиком: вы изменили '
                    .ProductStatusStoryFactory::RECHECK_FIELDS.', а такие правки проверяются заново. '
                    .'Чтобы вернуть товар в каталог, отправьте его на проверку.';
            }

            if ($outcome->contentChanged && in_array($outcome->statusBefore, self::IN_REVIEW, true)) {
                return 'Изменения сохранены. Прежняя проверка отменена, товар стал черновиком. Отправьте его на проверку снова.';
            }

            return 'Черновик сохранён. Покупатели его не видят. Когда карточка готова, нажмите «Отправить на проверку».';
        }

        return match (true) {
            in_array($status, self::IN_REVIEW, true) => 'Изменения сохранены. Товар по-прежнему на проверке.',
            $status === ProductModerationStatus::ChangesRequired => 'Изменения сохранены. Товар всё ещё ждёт исправлений: когда исправите замечания, нажмите «Отправить на проверку».',
            default => 'Изменения сохранены. Чтобы товар проверили снова, исправьте карточку по причине отказа и отправьте её на проверку.',
        };
    }

    /** Сообщение после удаления фото: удаление может вернуть товар в черновик и скрыть его из каталога. */
    public static function forImageDeleted(ProductModerationStatus $statusBefore, bool $wasPublic, Product $after): string
    {
        $returnedToDraft = $after->moderation_status === ProductModerationStatus::Draft
            && $statusBefore !== ProductModerationStatus::Draft;

        if (! $returnedToDraft) {
            return 'Фото удалено.';
        }

        return $wasPublic
            ? 'Фото удалено. Товар скрыт из каталога и стал черновиком: после смены фото его проверяют заново. '
                .'Отправьте его на проверку, чтобы он снова появился.'
            : 'Фото удалено. Товар стал черновиком: после смены фото его нужно отправить на проверку заново.';
    }

    /** Что сказать после отправки: проверка могла закончиться сразу (синхронная очередь, автоодобрение). */
    public static function afterSubmit(Product $product): string
    {
        return match ($product->moderation_status) {
            ProductModerationStatus::Approved => $product->is_active
                ? 'Товар прошёл проверку и опубликован.'
                : 'Товар прошёл проверку. Нажмите «Опубликовать», чтобы он появился в каталоге.',
            ProductModerationStatus::ChangesRequired => 'Проверка нашла, что нужно исправить. Замечания показаны на странице товара.',
            default => self::SENT,
        };
    }
}
