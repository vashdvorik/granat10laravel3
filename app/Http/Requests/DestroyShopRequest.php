<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Без собственного пароля (вход через Google) подтверждаем удаление названием магазина
        return $this->user()?->hasPassword()
            ? ['password' => ['required']]
            : ['confirmation' => ['required', 'string']];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Введите пароль для подтверждения.',
            'confirmation.required' => 'Введите название магазина для подтверждения.',
        ];
    }
}
