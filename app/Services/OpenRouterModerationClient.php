<?php

namespace App\Services;

use App\Contracts\AiModerationClient;
use App\Data\ModerationProvider;
use App\Data\ProductModerationPayload;
use App\Data\ProductModerationResult;

/** Проверка текста карточки товара (название, описание, характеристики, категория) через OpenRouter. */
class OpenRouterModerationClient implements AiModerationClient
{
    public function __construct(
        private readonly OpenRouterClient $openRouter,
        private readonly ProductModerationResultValidator $resultValidator,
        private readonly ProductModerationPrompt $prompt,
        private readonly ModerationResponseContract $contract,
    ) {}

    public function moderate(ProductModerationPayload $payload): ProductModerationResult
    {
        $answer = $this->openRouter->completeJson(
            scope: ModerationProvider::TEXT_SCOPE,
            model: (string) config('moderation.openrouter.text_model'),
            messages: [
                ['role' => 'system', 'content' => $this->prompt->instruction().' '.$this->contract->instruction()],
                ['role' => 'user', 'content' => $this->productData($payload)],
            ],
            timeout: (int) config('moderation.timeout', 60),
        );

        return $this->resultValidator->validate(
            result: $answer['data'],
            providerRequestId: $answer['request_id'],
            // фото проверяет отдельный клиент: по тексту нельзя сказать, что они проверены
            imagesAnalyzed: $payload->imagePaths === [],
        );
    }

    private function productData(ProductModerationPayload $payload): string
    {
        return (string) json_encode([
            'product_id' => $payload->productId,
            'name' => $payload->name,
            'description' => $payload->description,
            'features' => $payload->features,
            'category' => $payload->categoryName,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
