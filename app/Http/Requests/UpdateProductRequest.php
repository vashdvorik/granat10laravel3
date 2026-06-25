<?php

namespace App\Http\Requests;

class UpdateProductRequest extends ProductRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'expected_moderation_version' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'expected_moderation_version.integer' => 'Не удалось проверить версию товара. Обновите страницу.',
            'expected_moderation_version.min' => 'Указана некорректная версия товара.',
        ]);
    }
}
