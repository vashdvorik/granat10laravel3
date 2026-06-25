<?php

namespace Tests\Unit;

use App\Data\ProductModerationResult;
use App\Services\ProductModerationScopeFilter;
use PHPUnit\Framework\TestCase;

class ProductModerationScopeFilterTest extends TestCase
{
    public function test_certificate_requirement_is_ignored(): void
    {
        $result = (new ProductModerationScopeFilter)->filter(new ProductModerationResult(
            decision: 'review',
            riskScore: 0.7,
            violations: [[
                'code' => 'certificate_required',
                'confidence' => 0.8,
                'evidence' => 'Для устройства с аккумулятором нужно указать наличие сертификата.',
                'field' => 'description',
            ]],
            sellerMessage: 'Укажите наличие сертификата.',
        ));

        $this->assertSame('allow', $result->decision);
        $this->assertSame([], $result->violations);
        $this->assertNull($result->sellerMessage);
    }

    public function test_model_lineup_claim_is_ignored(): void
    {
        $result = (new ProductModerationScopeFilter)->filter(new ProductModerationResult(
            decision: 'review',
            riskScore: 0.8,
            violations: [[
                'code' => 'model_does_not_exist',
                'confidence' => 0.9,
                'evidence' => 'Такого товара нет в линейке Samsung.',
            ]],
        ));

        $this->assertSame('allow', $result->decision);
    }

    public function test_missing_specs_requirement_is_ignored(): void
    {
        $result = (new ProductModerationScopeFilter)->filter(new ProductModerationResult(
            decision: 'review',
            riskScore: 0.7,
            violations: [[
                'code' => 'insufficient_characteristics',
                'confidence' => 0.8,
                'evidence' => 'В карточке отсутствуют характеристики: частотный диапазон, импеданс.',
            ]],
        ));

        $this->assertSame('allow', $result->decision);
    }

    public function test_prohibited_goods_remain_blocking(): void
    {
        $result = (new ProductModerationScopeFilter)->filter(new ProductModerationResult(
            decision: 'review',
            riskScore: 0.9,
            violations: [[
                'code' => 'illegal_drugs',
                'confidence' => 0.9,
                'evidence' => 'Объявление похоже на продажу запрещённых веществ.',
            ]],
            sellerMessage: 'Удалите запрещённый товар.',
        ));

        $this->assertSame('review', $result->decision);
        $this->assertSame('illegal_drugs', $result->violations[0]['code']);
    }
}
