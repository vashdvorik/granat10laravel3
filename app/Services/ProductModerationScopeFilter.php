<?php

namespace App\Services;

use App\Data\ProductModerationResult;

class ProductModerationScopeFilter
{
    private const ALLOWED_CODES = [
        'spam',
        'nonsense',
        'profanity',
        'obscene_language',
        'offensive_language',
        'wrong_category',
        'category_mismatch',
        'prohibited_goods',
        'prohibited_services',
        'illegal_goods',
        'illegal_services',
        'illegal_drugs',
        'weapons',
        'alcohol',
        'adult_services',
        'fraud',
        'fraud_financial_scheme',
        'documents_money',
        'prohibited_image',
        'unsafe_image',
    ];

    private const OUT_OF_SCOPE_PATTERNS = [
        'сертифик',
        'лиценз',
        'аккумулятор',
        'батаре',
        'зарядк',
        'характеристик',
        'комплектац',
        'линейк',
        'модел',
        'не существует',
        'отсутствует описание',
        'добавьте описание',
        'частот',
        'импеданс',
        'поддержк',
        'техническ',
    ];

    public function filter(ProductModerationResult $result): ProductModerationResult
    {
        if ($result->violations === []) {
            return $result;
        }

        $violations = collect($result->violations)
            ->filter(fn (array $violation): bool => $this->isInScope($violation))
            ->values()
            ->all();

        if ($violations === []) {
            return new ProductModerationResult(
                decision: 'allow',
                riskScore: 0.0,
                violations: [],
                sellerMessage: null,
                providerRequestId: $result->providerRequestId,
                imagesAnalyzed: $result->imagesAnalyzed,
            );
        }

        return new ProductModerationResult(
            decision: $result->decision === 'allow' ? 'review' : $result->decision,
            riskScore: $result->riskScore,
            violations: $violations,
            sellerMessage: $this->sellerMessageInScope($result->sellerMessage) ? $result->sellerMessage : null,
            providerRequestId: $result->providerRequestId,
            imagesAnalyzed: $result->imagesAnalyzed,
        );
    }

    private function isInScope(array $violation): bool
    {
        $code = mb_strtolower((string) ($violation['code'] ?? ''));
        $text = mb_strtolower(implode(' ', [
            $code,
            (string) ($violation['evidence'] ?? ''),
            (string) ($violation['suggestion'] ?? ''),
        ]));

        if ($this->containsOutOfScopePattern($text)) {
            return false;
        }

        return in_array($code, self::ALLOWED_CODES, true);
    }

    private function sellerMessageInScope(?string $message): bool
    {
        return $message !== null && ! $this->containsOutOfScopePattern(mb_strtolower($message));
    }

    private function containsOutOfScopePattern(string $text): bool
    {
        foreach (self::OUT_OF_SCOPE_PATTERNS as $pattern) {
            if (str_contains($text, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
