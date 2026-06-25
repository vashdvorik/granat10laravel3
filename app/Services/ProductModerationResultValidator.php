<?php

namespace App\Services;

use App\Data\ProductModerationResult;
use App\Exceptions\AiModerationProviderException;

class ProductModerationResultValidator
{
    private const MAX_VIOLATIONS = 50;

    private const MAX_CODE_LENGTH = 100;

    private const MAX_EVIDENCE_LENGTH = 1000;

    private const MAX_SELLER_MESSAGE_LENGTH = 1500;

    /**
     * @param  array<string, mixed>  $result
     */
    public function validate(
        array $result,
        ?string $providerRequestId = null,
        bool $imagesAnalyzed = false,
    ): ProductModerationResult {
        $decision = $result['decision'] ?? null;
        $riskScore = $result['risk_score'] ?? null;
        $violations = $result['violations'] ?? null;
        $sellerMessage = $result['seller_message'] ?? null;

        if (
            ! is_string($decision)
            || ! in_array($decision, ['allow', 'review', 'block'], true)
            || ! $this->isValidScore($riskScore)
            || ! is_array($violations)
            || count($violations) > self::MAX_VIOLATIONS
            || ! $this->validViolations($violations)
            || ! $this->validSellerMessage($decision, $sellerMessage)
        ) {
            throw new AiModerationProviderException('AI API response failed validation.');
        }

        return new ProductModerationResult(
            decision: $decision,
            riskScore: (float) $riskScore,
            violations: $violations,
            sellerMessage: is_string($sellerMessage) ? trim($sellerMessage) : null,
            providerRequestId: $providerRequestId,
            imagesAnalyzed: $imagesAnalyzed,
        );
    }

    private function isValidScore(mixed $score): bool
    {
        return (is_int($score) || is_float($score))
            && is_finite((float) $score)
            && $score >= 0
            && $score <= 1;
    }

    /**
     * @param  array<int, mixed>  $violations
     */
    private function validViolations(array $violations): bool
    {
        foreach ($violations as $violation) {
            if (! is_array($violation)) {
                return false;
            }

            $code = $violation['code'] ?? null;
            $confidence = $violation['confidence'] ?? null;
            $evidence = $violation['evidence'] ?? null;
            $field = $violation['field'] ?? null;
            $suggestion = $violation['suggestion'] ?? null;

            if (
                ! is_string($code)
                || trim($code) === ''
                || mb_strlen($code) > self::MAX_CODE_LENGTH
                || ! $this->isValidScore($confidence)
                || ! is_string($evidence)
                || trim($evidence) === ''
                || mb_strlen($evidence) > self::MAX_EVIDENCE_LENGTH
                || ($field !== null && ! in_array($field, [
                    'name',
                    'description',
                    'features',
                    'category',
                    'images',
                    'general',
                ], true))
                || ($suggestion !== null && (
                    ! is_string($suggestion)
                    || trim($suggestion) === ''
                    || mb_strlen($suggestion) > self::MAX_EVIDENCE_LENGTH
                ))
            ) {
                return false;
            }
        }

        return true;
    }

    private function validSellerMessage(string $decision, mixed $sellerMessage): bool
    {
        if ($sellerMessage === null) {
            return $decision === 'allow';
        }

        if (
            ! is_string($sellerMessage)
            || trim($sellerMessage) === ''
            || mb_strlen($sellerMessage) > self::MAX_SELLER_MESSAGE_LENGTH
        ) {
            return false;
        }

        return true;
    }
}
