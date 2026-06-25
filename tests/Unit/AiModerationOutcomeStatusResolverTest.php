<?php

namespace Tests\Unit;

use App\Data\AutoModerationDecision;
use App\Data\ProductModerationResult;
use App\Enums\ProductModerationStatus;
use App\Services\AiModerationOutcomeStatusResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AiModerationOutcomeStatusResolverTest extends TestCase
{
    public function test_approved_decision_returns_approved_status(): void
    {
        $status = (new AiModerationOutcomeStatusResolver)->resolve(
            new ProductModerationResult('allow', 0),
            new AutoModerationDecision(true, 'ok'),
        );

        $this->assertSame(ProductModerationStatus::Approved, $status);
    }

    #[DataProvider('actionableViolations')]
    public function test_actionable_feedback_requires_editing(array $violation): void
    {
        $status = (new AiModerationOutcomeStatusResolver)->resolve(
            new ProductModerationResult(
                decision: 'review',
                riskScore: 0.8,
                violations: [$violation],
                sellerMessage: 'Исправьте карточку.',
            ),
            new AutoModerationDecision(false, 'review'),
        );

        $this->assertSame(ProductModerationStatus::ChangesRequired, $status);
    }

    public function test_non_actionable_result_requires_administrator_review(): void
    {
        $status = (new AiModerationOutcomeStatusResolver)->resolve(
            new ProductModerationResult('allow', 0.5),
            new AutoModerationDecision(false, 'risk'),
        );

        $this->assertSame(ProductModerationStatus::NeedsReview, $status);
    }

    public static function actionableViolations(): array
    {
        return [
            'evidence' => [[
                'code' => 'profanity',
                'evidence' => 'В описании обнаружена ненормативная лексика.',
            ]],
            'suggestion' => [[
                'code' => 'wrong_category',
                'suggestion' => 'Выберите подходящую категорию.',
            ]],
        ];
    }
}
