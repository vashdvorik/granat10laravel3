<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация загрузки изображений
 */
class UploadImageRequest extends FormRequest
{
    /**
     * Определяет авторизован ли пользователь для этого запроса
     */
    public function authorize(): bool
    {
        // Только авторизованные пользователи с магазином могут загружать изображения
        return $this->user()?->seller !== null;
    }

    /**
     * Правила валидации
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120', // 5MB
                'dimensions:min_width=400,min_height=400,max_width=4000,max_height=4000',
            ],
        ];
    }

    /**
     * Сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            'images.required' => __('validation.required', ['attribute' => __('attributes.images')]),
            'images.array' => __('validation.array', ['attribute' => __('attributes.images')]),
            'images.min' => __('validation.min.array', ['attribute' => __('attributes.images'), 'min' => 1]),
            'images.max' => __('validation.max.array', ['attribute' => __('attributes.images'), 'max' => 10]),
            'images.*.required' => __('validation.required', ['attribute' => __('attributes.image')]),
            'images.*.image' => __('validation.image', ['attribute' => __('attributes.image')]),
            'images.*.mimes' => __('validation.mimes', ['attribute' => __('attributes.image'), 'values' => 'jpeg, jpg, png, webp']),
            'images.*.max' => __('validation.max.file', ['attribute' => __('attributes.image'), 'max' => 5120]),
            'images.*.dimensions' => __('validation.dimensions', ['attribute' => __('attributes.image')]),
        ];
    }

    /**
     * Имена атрибутов для сообщений об ошибках
     */
    public function attributes(): array
    {
        return [
            'images' => __('attributes.images'),
            'images.*' => __('attributes.image'),
        ];
    }
}
