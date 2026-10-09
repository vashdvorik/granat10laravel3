<?php

namespace App\Services;

use App\Contracts\ProductImageModerationClient;
use App\Data\ModerationProvider;
use App\Data\ProductModerationPayload;
use App\Data\ProductModerationResult;

/** Проверка фотографий товара через OpenRouter (нужна модель, которая умеет читать изображения). */
class OpenRouterImageModerationClient implements ProductImageModerationClient
{
    public function __construct(
        private readonly OpenRouterClient $openRouter,
        private readonly ProductModerationResultValidator $resultValidator,
        private readonly ProductModerationImagePreparer $imagePreparer,
        private readonly ProductImageModerationPrompt $prompt,
        private readonly ModerationResponseContract $contract,
    ) {}

    public function moderate(ProductModerationPayload $payload): ProductModerationResult
    {
        $images = $this->imagePreparer->prepare($payload->imagePaths);

        if ($images === []) {
            return new ProductModerationResult(
                decision: 'review',
                riskScore: 0.5,
                violations: [[
                    'code' => 'empty_image',
                    'confidence' => 1.0,
                    'evidence' => 'Изображения товара не удалось подготовить для проверки.',
                    'field' => 'images',
                    'suggestion' => 'Загрузите корректное изображение товара.',
                ]],
                sellerMessage: 'Загрузите корректное изображение товара.',
                imagesAnalyzed: false,
            );
        }

        $content = [[
            'type' => 'text',
            'text' => (string) json_encode([
                'product_id' => $payload->productId,
                'name' => $payload->name,
                'description' => $payload->description,
                'features' => $payload->features,
                'category' => $payload->categoryName,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]];

        foreach ($images as $image) {
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => "data:{$image->mimeType};base64,{$image->base64Data}",
                    'detail' => 'low',
                ],
            ];
        }

        $answer = $this->openRouter->completeJson(
            scope: ModerationProvider::IMAGE_SCOPE,
            model: (string) config('moderation.openrouter.image_model'),
            messages: [
                ['role' => 'system', 'content' => $this->prompt->instruction().' '.$this->contract->instruction()],
                ['role' => 'user', 'content' => $content],
            ],
            timeout: (int) config('moderation.images.timeout', 60),
        );

        return $this->resultValidator->validate(
            result: $answer['data'],
            providerRequestId: $answer['request_id'],
            imagesAnalyzed: count($images) === count($payload->imagePaths),
        );
    }
}
