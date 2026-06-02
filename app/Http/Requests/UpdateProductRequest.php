<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация обновления продукта
 */
class UpdateProductRequest extends FormRequest
{
    /**
     * Определяет авторизован ли пользователь для этого запроса
     */
    public function authorize(): bool
    {
        $product = $this->route('product');

        // Проверяем что продукт принадлежит текущему пользователю
        return $product && $this->user()?->id === $product->user_id;
    }

    /**
     * Правила валидации
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'min:50'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999999.99'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'is_vip' => ['boolean'],
            'is_active' => ['boolean'],
            'images' => ['array', 'max:10'],
            'images.*' => ['integer', 'exists:images,id'],
            'main_image_id' => ['nullable', 'integer', 'exists:images,id'],
            'condition' => ['sometimes', 'required', Rule::in(['new', 'used', 'refurbished'])],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => __('attributes.name')]),
            'name.max' => __('validation.max.string', ['attribute' => __('attributes.name'), 'max' => 255]),
            'description.required' => __('validation.required', ['attribute' => __('attributes.description')]),
            'description.min' => __('validation.min.string', ['attribute' => __('attributes.description'), 'min' => 50]),
            'price.required' => __('validation.required', ['attribute' => __('attributes.price')]),
            'price.numeric' => __('validation.numeric', ['attribute' => __('attributes.price')]),
            'category_id.required' => __('validation.required', ['attribute' => __('attributes.category')]),
            'category_id.exists' => __('validation.exists', ['attribute' => __('attributes.category')]),
            'images.max' => __('validation.max.array', ['attribute' => __('attributes.images'), 'max' => 10]),
        ];
    }

    /**
     * Имена атрибутов для сообщений об ошибках
     */
    public function attributes(): array
    {
        return [
            'name' => __('attributes.product_name'),
            'description' => __('attributes.description'),
            'price' => __('attributes.price'),
            'category_id' => __('attributes.category'),
            'images' => __('attributes.images'),
            'condition' => __('attributes.condition'),
            'stock_quantity' => __('attributes.stock_quantity'),
            'sku' => __('attributes.sku'),
        ];
    }
}
