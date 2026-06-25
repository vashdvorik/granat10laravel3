<?php

namespace Tests\Unit;

use App\Exceptions\AiModerationBudgetExceededException;
use App\Services\AiModerationRequestBudget;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AiModerationRequestBudgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('moderation.provider', 'test');
        config()->set('moderation.budget.hourly_requests', 2);
        config()->set('moderation.budget.daily_requests', 3);
    }

    public function test_budget_tracks_usage_and_blocks_hourly_overage(): void
    {
        $budget = app(AiModerationRequestBudget::class);

        $budget->consume();
        $budget->consume();

        $this->assertSame(2, $budget->usage()['hourly_used']);

        $this->expectException(AiModerationBudgetExceededException::class);
        $budget->consume();
    }

    public function test_daily_failure_rolls_back_hourly_counter(): void
    {
        config()->set('moderation.budget.hourly_requests', 10);
        config()->set('moderation.budget.daily_requests', 1);
        $budget = app(AiModerationRequestBudget::class);
        $budget->consume();

        try {
            $budget->consume();
            $this->fail('Daily limit was not enforced.');
        } catch (AiModerationBudgetExceededException) {
            $this->assertSame(1, $budget->usage()['hourly_used']);
            $this->assertSame(1, $budget->usage()['daily_used']);
        }
    }

    public function test_zero_limit_disables_budget(): void
    {
        config()->set('moderation.budget.hourly_requests', 0);
        config()->set('moderation.budget.daily_requests', 0);
        $budget = app(AiModerationRequestBudget::class);

        $budget->consume();

        $this->assertSame(0, $budget->usage()['hourly_used']);
    }
}
