<?php

declare(strict_types=1);

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

final class ValidateCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'shipping_rate_id' => [
                'required',
                'integer',
                'exists:shipping_rates,id',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shipping_rate_id.required' => 'Please select a shipping method.',

            'shipping_rate_id.integer' => 'The selected shipping method is invalid.',

            'shipping_rate_id.exists' => 'The selected shipping method is no longer available.',
        ];
    }
}
