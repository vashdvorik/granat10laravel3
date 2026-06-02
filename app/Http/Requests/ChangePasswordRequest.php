<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Валидация смены пароля
 */
class ChangePasswordRequest extends FormRequest
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
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers(),
            ],
        ];
    }

    /**
     * Имена атрибутов для сообщений об ошибках
     */
    public function attributes(): array
    {
        return [
            'current_password' => __('attributes.current_password'),
            'password' => __('attributes.new_password'),
        ];
    }
}
