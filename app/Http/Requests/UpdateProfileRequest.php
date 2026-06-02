<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация обновления профиля пользователя
 */
class UpdateProfileRequest extends FormRequest
{
    /**
     * Определяет авторизован ли пользователь для этого запроса
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Имена атрибутов для сообщений об ошибках
     */
    public function attributes(): array
    {
        return [
            'name' => __('attributes.name'),
            'email' => __('attributes.email'),
            'phone' => __('attributes.phone'),
        ];
    }
}
