<?php

namespace App\Data;

/**
 * Единственный сервис ИИ-модерации — OpenRouter. Названия ниже попадают в журнал проверок
 * (`product_moderation_runs.provider`) и в учёт расхода запросов; префикс `image_` отличает проверки фото.
 */
final class ModerationProvider
{
    public const NAME = 'OpenRouter';

    /** Значение `provider` у проверок текста. */
    public const TEXT = 'openrouter';

    /** Значение `provider` у проверок фото. */
    public const IMAGE = 'image_openrouter';

    /** Ключи учёта расхода запросов (часовой и суточный лимиты). */
    public const TEXT_SCOPE = 'text:openrouter';

    public const IMAGE_SCOPE = 'image:openrouter';
}
