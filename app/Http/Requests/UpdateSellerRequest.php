<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация обновления магазина
 */
class UpdateSellerRequest extends FormRequest
{
    /**
     * Определяет авторизован ли пользователь для этого запроса
     */
    public function authorize(): bool
    {
        $seller = $this->user()?->seller;

        return $seller !== null;
    }

    /**
     * Правила валидации
     */
    public function rules(): array
    {
        $seller = $this->user()->seller;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('sellers')->ignore($seller?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Имена атрибутов для сообщений об ошибках
     */
    public function attributes(): array
    {
        return [
            'name' => __('attributes.shop_name'),
            'slug' => __('attributes.slug'),
            'description' => __('attributes.shop_description'),
            'phone' => __('attributes.phone'),
            'address' => __('attributes.address'),
            'city' => __('attributes.city'),
        ];
    }
}
