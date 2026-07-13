<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreparePackageTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'active_product_ids' => ['nullable', 'array'],
            'active_product_ids.*' => ['integer'],
            'vip_product_ids' => ['nullable', 'array'],
            'vip_product_ids.*' => ['integer'],
        ];
    }

    public function activeProductIds(): array
    {
        return $this->integerList('active_product_ids');
    }

    public function vipProductIds(): array
    {
        return $this->integerList('vip_product_ids');
    }

    private function integerList(string $key): array
    {
        return collect($this->input($key, []))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
