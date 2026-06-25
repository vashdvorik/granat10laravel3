<?php

namespace Tests\Unit;

use App\Services\ProductModerationPrompt;
use PHPUnit\Framework\TestCase;

class ProductModerationPromptTest extends TestCase
{
    public function test_prompt_limits_ai_to_marketplace_safety_rules(): void
    {
        $prompt = (new ProductModerationPrompt)->instruction();

        $this->assertStringContainsString('если проблема не входит в список разрешённых нарушений ниже — верни allow', $prompt);
        $this->assertStringContainsString('Разрешённые коды violations', $prompt);
        $this->assertStringContainsString('prohibited_goods', $prompt);
        $this->assertStringContainsString('profanity', $prompt);
        $this->assertStringContainsString('category_mismatch', $prompt);
        $this->assertStringContainsString('prohibited_image', $prompt);
    }

    public function test_prompt_forbids_strict_out_of_scope_reasons(): void
    {
        $prompt = (new ProductModerationPrompt)->instruction();

        $this->assertStringContainsString('Запрещено возвращать нарушения и замечания', $prompt);
        $this->assertStringContainsString('нет сертификата', $prompt);
        $this->assertStringContainsString('нет технических характеристик', $prompt);
        $this->assertStringContainsString('модель товара якобы не существует', $prompt);
        $this->assertStringContainsString('decision = allow', $prompt);
        $this->assertStringContainsString('seller_message = null', $prompt);
    }
}
