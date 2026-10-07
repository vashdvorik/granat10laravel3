<?php

namespace App\Http\Requests;

use App\Enums\ProductSaveAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:70'],
            'price' => ['required', 'numeric', 'min:0'],
            'lowprice' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'category_id' => ['required', 'exists:categories,id'],
            'desc' => ['nullable', 'string', 'max:500'],
            'features1' => ['nullable', 'string', 'max:30'],
            'features2' => ['nullable', 'string', 'max:30'],
            'features3' => ['nullable', 'string', 'max:30'],
            'features4' => ['nullable', 'string', 'max:30'],
            'save_action' => ['required', Rule::enum(ProductSaveAction::class)],
            'can_order' => ['boolean'],
            'delivery_enabled' => ['boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Поле "Название товара" обязательно для заполнения',
            'name.max' => 'Название товара не может быть длиннее 70 символов',
            'price.required' => 'Укажите цену товара',
            'price.numeric' => 'Цена должна быть числом',
            'price.min' => 'Цена не может быть отрицательной',
            'category_id.required' => 'Выберите категорию товара',
            'category_id.exists' => 'Выбранная категория не существует',
            'lowprice.numeric' => 'Цена со скидкой должна быть числом',
            'lowprice.min' => 'Цена со скидкой не может быть отрицательной',
            'lowprice.lt' => 'Цена со скидкой должна быть меньше обычной цены',
            'images.max' => 'Можно загрузить максимум 5 изображений',
            'images.*.image' => 'Файл должен быть изображением',
            'images.*.mimes' => 'Поддерживаются только форматы: JPEG, PNG, JPG, WebP',
            'images.*.max' => 'Размер изображения не должен превышать 5 МБ',
            'save_action.required' => 'Выберите, сохранить черновик или отправить товар на проверку',
            'save_action.enum' => 'Выбрано недопустимое действие с товаром',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('save_action') && $this->has('is_active')) {
            $this->merge([
                'save_action' => $this->boolean('is_active')
                    ? ProductSaveAction::Submit->value
                    : ProductSaveAction::Draft->value,
            ]);
        }
    }
}
