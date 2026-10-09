<?php

namespace App\Services;

use App\Data\ProductStatusStory;
use App\Enums\ProductModerationStatus;
use App\Models\Product;

/**
 * Единственное место, где состояние товара объясняется продавцу словами.
 * Служебные тексты проверки («риск выше порога», «ИИ запросил ручную проверку») сюда не попадают:
 * продавцу показываются только замечания, которые он может исправить.
 */
class ProductStatusStoryFactory
{
    /** Что при редактировании запускает повторную проверку (общий текст для форм и подсказок). */
    public const RECHECK_FIELDS = 'название, описание, характеристики, категорию или фото';

    public function __construct(
        private readonly ProductVisibilityResolver $visibility,
    ) {}

    public function make(Product $product): ProductStatusStory
    {
        return match ($product->moderation_status) {
            ProductModerationStatus::Draft => $this->draft($product),
            ProductModerationStatus::Pending => new ProductStatusStory(
                key: 'queued',
                headline: 'Ждёт проверки',
                explanation: 'Товар стоит в очереди на проверку. Покупатели его пока не видят.',
                nextStep: 'Ничего делать не нужно: результат придёт в уведомления (колокольчик вверху). '
                    .'Если изменить '.self::RECHECK_FIELDS.', проверка начнётся заново.',
                tone: 'warning',
                icon: 'clock',
            ),
            ProductModerationStatus::Processing => new ProductStatusStory(
                key: 'checking',
                headline: 'Идёт проверка',
                explanation: 'Товар сейчас проверяется. Покупатели его пока не видят.',
                nextStep: 'Ничего делать не нужно: результат придёт в уведомления (колокольчик вверху).',
                tone: 'warning',
                icon: 'search',
            ),
            ProductModerationStatus::NeedsReview,
            ProductModerationStatus::Failed => new ProductStatusStory(
                key: 'manual_review',
                headline: 'Проверяет администратор',
                explanation: 'Товар проверяет сотрудник сайта. Это не отказ: после проверки товар опубликуется '
                    .'или вы получите понятные замечания.',
                nextStep: 'Ничего делать не нужно: мы пришлём уведомление. '
                    .'Если изменить '.self::RECHECK_FIELDS.', проверка начнётся заново.',
                tone: 'info',
                icon: 'user-check',
            ),
            ProductModerationStatus::ChangesRequired => new ProductStatusStory(
                key: 'changes_required',
                headline: 'Нужно исправить',
                explanation: 'Пока замечания не исправлены, товар не опубликован.',
                nextStep: 'Исправьте их и отправьте товар на проверку снова.',
                tone: 'warning',
                icon: 'edit',
                remark: $this->remark($product),
            ),
            ProductModerationStatus::Rejected => new ProductStatusStory(
                key: 'rejected',
                headline: 'Отклонён',
                explanation: 'Товар не прошёл проверку и в таком виде опубликован не будет.',
                nextStep: 'Если причину можно исправить, измените карточку и отправьте её на проверку снова. '
                    .'Если считаете отказ ошибкой, свяжитесь с администрацией сайта.',
                tone: 'danger',
                icon: 'times-circle',
                remark: $this->remark($product),
                remarkTitle: 'Причина отказа',
            ),
            ProductModerationStatus::Approved => $this->approved($product),
        };
    }

    private function draft(Product $product): ProductStatusStory
    {
        // Замечания прошлой проверки сохраняются в черновике, пока продавец не отправит товар снова
        $remark = $this->remark($product);

        if ($remark !== null) {
            return new ProductStatusStory(
                key: 'draft',
                headline: 'Черновик',
                explanation: 'Покупатели этот товар не видят.',
                nextStep: 'Исправьте замечания и отправьте товар на проверку.',
                tone: 'warning',
                icon: 'file-edit-alt',
                remark: $remark,
                remarkTitle: 'Замечания прошлой проверки',
            );
        }

        return new ProductStatusStory(
            key: 'draft',
            headline: 'Черновик',
            explanation: 'Покупатели этот товар не видят: он сохранён только у вас и не отправлен на проверку.',
            nextStep: 'Когда карточка готова, нажмите «Отправить на проверку». После одобрения товар опубликуется сам.',
            tone: 'gray',
            icon: 'file-edit-alt',
        );
    }

    private function approved(Product $product): ProductStatusStory
    {
        $snapshot = $this->visibility->snapshot($product);

        if (! $snapshot->isModerationCurrent) {
            return new ProductStatusStory(
                key: 'outdated',
                headline: 'Нужна новая проверка',
                explanation: 'Вы изменили товар после проверки, поэтому покупатели его не видят.',
                nextStep: 'Отправьте его на проверку ещё раз.',
                tone: 'warning',
                icon: 'redo',
            );
        }

        if (! $snapshot->isActive) {
            return new ProductStatusStory(
                key: 'approved_hidden',
                headline: 'Одобрен, не опубликован',
                explanation: 'Проверка пройдена, но сейчас покупатели товар не видят.',
                nextStep: $snapshot->hasImages
                    ? 'Нажмите «Опубликовать», и товар появится в каталоге и поиске.'
                    : 'Добавьте хотя бы одно фото, затем нажмите «Опубликовать».',
                tone: 'info',
                icon: 'eye-slash',
            );
        }

        if ($snapshot->sellerHidden) {
            return new ProductStatusStory(
                key: 'seller_hidden',
                headline: 'Магазин скрыт',
                explanation: 'Товар не показывается покупателям, пока магазин скрыт администратором.',
                nextStep: 'Причина указана в красном уведомлении вверху страницы. Исправьте её и напишите в поддержку: '
                    .'после проверки магазин и товары вернутся на сайт.',
                tone: 'danger',
                icon: 'ban',
            );
        }

        if (! $snapshot->hasImages) {
            return new ProductStatusStory(
                key: 'no_images',
                headline: 'Нет фото',
                explanation: 'Без фото товар не показывается покупателям.',
                nextStep: 'Добавьте хотя бы одно фото.',
                tone: 'warning',
                icon: 'image-slash',
            );
        }

        return new ProductStatusStory(
            key: 'published',
            headline: 'Опубликован',
            explanation: 'Товар виден покупателям в каталоге и поиске.',
            nextStep: null,
            tone: 'success',
            icon: 'check-circle',
        );
    }

    /** Замечание проверяющего, очищенное от пустоты. */
    private function remark(Product $product): ?string
    {
        $summary = trim((string) $product->moderation_summary);

        return $summary === '' ? null : $summary;
    }
}
