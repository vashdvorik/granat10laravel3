<?php

namespace App\Services;

use App\Data\ProductModerationResult;

class ProductModerationFeedbackService
{
    public function forSeller(ProductModerationResult $result, string $fallback): string
    {
        $sellerMessage = trim((string) $result->sellerMessage);

        if ($sellerMessage !== '') {
            return $this->withCorrectionCall($sellerMessage);
        }

        $reasons = collect($result->violations)
            ->map(fn (mixed $violation): ?string => is_array($violation)
                ? $this->violationMessage($violation)
                : null)
            ->filter()
            ->unique()
            ->take(3)
            ->values();

        if ($reasons->isNotEmpty()) {
            return $this->withCorrectionCall(
                'Автоматическая проверка обнаружила проблемы: '
                .$reasons->implode('; ')
                .'.'
            );
        }

        return $fallback;
    }

    private function withCorrectionCall(string $message): string
    {
        if (
            str_contains(mb_strtolower($message), 'исправ')
            && (str_contains(mb_strtolower($message), 'модерац') || str_contains(mb_strtolower($message), 'проверк'))
        ) {
            return $message;
        }

        return rtrim($message, ". \t\n\r\0\x0B")
            .'. Исправьте карточку товара и отправьте её на проверку повторно.';
    }

    private function violationMessage(array $violation): ?string
    {
        $evidence = trim((string) ($violation['evidence'] ?? ''));

        if ($evidence !== '') {
            return rtrim($evidence, ". \t\n\r\0\x0B");
        }

        return match ($violation['code'] ?? null) {
            'profanity', 'obscene_language', 'offensive_language' => 'в тексте обнаружена ненормативная или оскорбительная лексика',
            'spam' => 'текст похож на спам',
            'wrong_category' => 'содержание товара может не соответствовать выбранной категории',
            'weapons' => 'товар может относиться к категории с ограничениями',
            default => null,
        };
    }
}
