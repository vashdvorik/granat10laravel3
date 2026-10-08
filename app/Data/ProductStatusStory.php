<?php

namespace App\Data;

/**
 * Состояние товара человеческим языком: что это значит для продавца и что делать дальше.
 * Один источник текстов для карточки, страницы товара, формы редактирования и уведомлений.
 */
final readonly class ProductStatusStory
{
    public function __construct(
        /** draft | queued | checking | manual_review | changes_required | rejected | approved_hidden | published | outdated | no_images */
        public string $key,
        /** Короткое название состояния: «Черновик», «Нужно исправить». */
        public string $headline,
        /** Что это значит и видят ли товар покупатели. */
        public string $explanation,
        /** Что продавцу делать дальше (или что произойдёт само). */
        public ?string $nextStep,
        /** gray | info | warning | success | danger */
        public string $tone,
        /** Иконка Unicons без префикса uil-. */
        public string $icon,
        /** Замечание проверяющего (только там, где продавец может его исправить). */
        public ?string $remark = null,
        public ?string $remarkTitle = null,
    ) {}
}
