<?php

declare(strict_types=1);

namespace App\Http\Requests\Frontend\Cart;

use Illuminate\Foundation\Http\FormRequest;

final class ApplyCartPromoCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:100',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');

        if (! is_string($code)) {
            return;
        }

        $this->merge([
            'code' => mb_strtoupper(
                trim($code),
            ),
        ]);
    }
}
