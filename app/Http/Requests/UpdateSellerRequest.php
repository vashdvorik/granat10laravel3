<?php

namespace App\Http\Requests;

use App\Models\Seller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSellerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $seller = $this->route('seller');
        $sellerId = $seller instanceof Seller ? $seller->id : null;

        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('sellers', 'name')->ignore($sellerId)],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^\d+$/'],
            'settlement_id' => [
                'required',
                'integer',
                Rule::exists('settlements', 'id')->where('is_active', true),
            ],
            'sellerplace' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'link' => ['nullable', 'url', 'max:191'],
            'link_map' => ['nullable', 'url', 'max:2000'],
            'telegram' => ['nullable', 'string', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'viber' => ['nullable', 'string', 'max:255'],
            'can_order' => ['boolean'],
            'delivery_enabled' => ['boolean'],
            'delivery_terms' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }
}
