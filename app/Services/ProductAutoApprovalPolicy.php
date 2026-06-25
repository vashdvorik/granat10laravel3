<?php

namespace App\Services;

use App\Data\AutoModerationDecision;
use App\Data\ProductModerationResult;
use App\Models\Product;
use App\Models\ProductModerationRun;

class ProductAutoApprovalPolicy
{
    public function evaluate(Product $product, ProductModerationResult $result): AutoModerationDecision
    {
        if ($result->decision !== 'allow') {
            return new AutoModerationDecision(false, 'ИИ обнаружил проблему или запросил ручную проверку.');
        }

        if ($result->violations !== []) {
            return new AutoModerationDecision(false, 'Ответ ИИ содержит признаки нарушений.');
        }

        if ($this->requiresSeparateImageModeration($product) && ! $this->hasPassedImageModeration($product)) {
            return new AutoModerationDecision(false, 'Ожидается автоматическая проверка изображений товара.');
        }

        if ($this->requiresImageAnalysis() && $product->images()->exists() && ! $result->imagesAnalyzed) {
            return new AutoModerationDecision(false, 'Изображения товара не были полностью проверены нейросетью.');
        }

        $maxRisk = (float) config('moderation.auto_approve.max_risk_score', 0.10);

        if ($result->riskScore > $maxRisk) {
            return new AutoModerationDecision(false, 'Оценка риска выше порога автоматической публикации.');
        }

        $excludedAliases = config('moderation.auto_approve.excluded_category_aliases', []);

        $product->loadMissing('category');

        if (in_array($product->category?->alias, $excludedAliases, true)) {
            return new AutoModerationDecision(false, 'Для этой категории автоматическая публикация запрещена.');
        }

        $localRun = ProductModerationRun::query()
            ->where('product_id', $product->id)
            ->where('content_version', $product->moderation_version)
            ->where('provider', 'local')
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        if ($localRun === null || $localRun->decision !== 'allow') {
            return new AutoModerationDecision(false, 'Локальная проверка не разрешила автоматическую публикацию.');
        }

        return new AutoModerationDecision(true, 'Все автоматические проверки пройдены.');
    }

    public function evaluateAfterImageModeration(Product $product, ProductModerationResult $imageResult): AutoModerationDecision
    {
        if ($imageResult->decision !== 'allow') {
            return new AutoModerationDecision(false, 'ИИ обнаружил проблему на изображениях товара.');
        }

        if ($imageResult->violations !== []) {
            return new AutoModerationDecision(false, 'Ответ ИИ содержит признаки нарушений на изображениях.');
        }

        $maxRisk = (float) config('moderation.auto_approve.max_risk_score', 0.10);

        if ($imageResult->riskScore > $maxRisk) {
            return new AutoModerationDecision(false, 'Оценка риска изображений выше порога автоматической публикации.');
        }

        if (! $this->hasPassedTextModeration($product)) {
            return new AutoModerationDecision(false, 'Текстовая проверка товара ещё не завершена успешно.');
        }

        $localRun = ProductModerationRun::query()
            ->where('product_id', $product->id)
            ->where('content_version', $product->moderation_version)
            ->where('provider', 'local')
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        if ($localRun === null || $localRun->decision !== 'allow') {
            return new AutoModerationDecision(false, 'Локальная проверка не разрешила автоматическую публикацию.');
        }

        return new AutoModerationDecision(true, 'Текст и изображения успешно прошли автоматическую проверку.');
    }

    public function shouldWaitForImageModeration(Product $product, AutoModerationDecision $decision): bool
    {
        return $decision->reason === 'Ожидается автоматическая проверка изображений товара.'
            && $this->requiresSeparateImageModeration($product);
    }

    private function requiresImageAnalysis(): bool
    {
        if (! config('moderation.vision.enabled', true)) {
            return false;
        }

        return match (config('moderation.provider')) {
            'google' => true,
            'openai_compatible' => (bool) config('moderation.openai_compatible.vision_enabled', false),
            default => false,
        };
    }

    private function requiresSeparateImageModeration(Product $product): bool
    {
        return (bool) config('moderation.images.enabled', false)
            && $product->images()->exists();
    }

    private function hasPassedImageModeration(Product $product): bool
    {
        return ProductModerationRun::query()
            ->where('product_id', $product->id)
            ->where('content_version', $product->moderation_version)
            ->where('provider', 'like', 'image_%')
            ->where('status', 'completed')
            ->where('decision', 'allow')
            ->exists();
    }

    private function hasPassedTextModeration(Product $product): bool
    {
        return ProductModerationRun::query()
            ->where('product_id', $product->id)
            ->where('content_version', $product->moderation_version)
            ->where('provider', '!=', 'local')
            ->where('provider', 'not like', 'image_%')
            ->where('status', 'completed')
            ->where('decision', 'allow')
            ->exists();
    }
}
