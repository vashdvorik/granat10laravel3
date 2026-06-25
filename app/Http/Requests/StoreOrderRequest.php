<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:100'],
            'customer_phone' => ['required', 'string', 'min:6', 'max:30'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'fulfillment_method' => ['required', Rule::in(['pickup', 'delivery'])],
            'delivery_address' => [
                Rule::requiredIf($this->input('fulfillment_method') === 'delivery'),
                'nullable',
                'string',
                'max:500',
            ],
            'customer_comment' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_name' => 'имя',
            'customer_phone' => 'телефон',
            'quantity' => 'количество',
            'fulfillment_method' => 'способ получения',
            'delivery_address' => 'адрес доставки',
            'customer_comment' => 'комментарий',
            'consent' => 'согласие на обработку данных',
        ];
    }
}
