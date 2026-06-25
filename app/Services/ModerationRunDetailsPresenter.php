<?php

namespace App\Services;

use App\Models\ProductModerationRun;

class ModerationRunDetailsPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(ProductModerationRun $run): array
    {
        return [
            'summary' => $this->summary($run),
            'reasons' => $this->reasons($run),
            'imageResult' => $run->image_result ?? [],
            'textResult' => $run->text_result ?? [],
            'error' => $run->error_message,
        ];
    }

    /**
     * @return array<int, array{label: string, value: string|null}>
     */
    private function summary(ProductModerationRun $run): array
    {
        return [
            ['label' => 'Провайдер', 'value' => $run->provider],
            ['label' => 'Модель', 'value' => $run->model],
            ['label' => 'Статус', 'value' => $run->status],
            ['label' => 'Решение ИИ', 'value' => $run->decision],
            ['label' => 'Риск', 'value' => $run->risk_score !== null ? (string) $run->risk_score : null],
            ['label' => 'ID запроса', 'value' => $run->provider_request_id],
        ];
    }

    /**
     * @return array<int, array{code: string|null, field: string|null, evidence: string|null, suggestion: string|null, image_index: int|null}>
     */
    private function reasons(ProductModerationRun $run): array
    {
        return collect($run->reasons ?? [])
            ->filter(fn (mixed $reason): bool => is_array($reason))
            ->map(fn (array $reason): array => [
                'code' => isset($reason['code']) ? (string) $reason['code'] : null,
                'field' => isset($reason['field']) ? (string) $reason['field'] : null,
                'evidence' => isset($reason['evidence']) ? (string) $reason['evidence'] : null,
                'suggestion' => isset($reason['suggestion']) ? (string) $reason['suggestion'] : null,
                'image_index' => is_numeric($reason['image_index'] ?? null) ? (int) $reason['image_index'] : null,
            ])
            ->values()
            ->all();
    }
}
