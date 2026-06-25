<?php

namespace Tests\Unit;

use App\Exceptions\AiModerationProviderException;
use App\Services\ProductModerationResultValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProductModerationResultValidatorTest extends TestCase
{
    public function test_valid_result_is_converted_to_dto(): void
    {
        $result = (new ProductModerationResultValidator)->validate([
            'decision' => 'review',
            'risk_score' => 0.75,
            'violations' => [[
                'code' => 'prohibited_content',
                'confidence' => 0.9,
                'evidence' => 'Обнаружено запрещённое содержание.',
            ]],
            'seller_message' => 'Исправьте описание и повторно отправьте товар на проверку.',
        ], 'request-1');

        $this->assertSame('review', $result->decision);
        $this->assertSame(0.75, $result->riskScore);
        $this->assertSame('request-1', $result->providerRequestId);
    }

    #[DataProvider('invalidResults')]
    public function test_invalid_result_is_rejected(array $result): void
    {
        $this->expectException(AiModerationProviderException::class);
        $this->expectExceptionMessage('failed validation');

        (new ProductModerationResultValidator)->validate($result);
    }

    public static function invalidResults(): array
    {
        $valid = [
            'decision' => 'allow',
            'risk_score' => 0.1,
            'violations' => [],
            'seller_message' => null,
        ];

        return [
            'unknown decision' => [[...$valid, 'decision' => 'approve']],
            'numeric string risk' => [[...$valid, 'risk_score' => '0.1']],
            'risk below range' => [[...$valid, 'risk_score' => -0.1]],
            'risk above range' => [[...$valid, 'risk_score' => 1.1]],
            'missing violations' => [[
                'decision' => 'allow',
                'risk_score' => 0.1,
                'seller_message' => null,
            ]],
            'invalid confidence' => [[...$valid, 'violations' => [[
                'code' => 'test',
                'confidence' => 2,
                'evidence' => 'Нарушение.',
            ]]]],
            'empty violation code' => [[...$valid, 'violations' => [[
                'code' => '',
                'confidence' => 0.5,
                'evidence' => 'Нарушение.',
            ]]]],
            'review without seller message' => [[...$valid, 'decision' => 'review']],
            'block with empty seller message' => [[
                ...$valid,
                'decision' => 'block',
                'seller_message' => ' ',
            ]],
            'unknown issue field' => [[...$valid, 'violations' => [[
                'code' => 'test',
                'confidence' => 0.5,
                'evidence' => 'Нарушение.',
                'field' => 'price',
            ]]]],
            'empty suggestion' => [[...$valid, 'violations' => [[
                'code' => 'test',
                'confidence' => 0.5,
                'evidence' => 'Нарушение.',
                'suggestion' => ' ',
            ]]]],
        ];
    }
}
