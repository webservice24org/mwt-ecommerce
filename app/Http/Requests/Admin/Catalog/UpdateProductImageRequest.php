<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use App\Domain\Catalog\Data\UpdateProductImageData;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductImageRequest extends FormRequest
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
            'alt_text' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('alt_text')) {
            $value = $this->input('alt_text');

            $this->merge([
                'alt_text' => is_string($value)
                        ? (
                            trim($value) === ''
                                ? null
                                : trim($value)
                        )
                        : null,
            ]);
        }
    }

    public function toData(): UpdateProductImageData
    {
        return new UpdateProductImageData(
            altText: $this->validated(
                'alt_text',
            ),
        );
    }
}
