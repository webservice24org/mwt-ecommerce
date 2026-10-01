<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\PageBuilder;

use Illuminate\Foundation\Http\FormRequest;

final class StorePageBuilderImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Please select an image to upload.',

            'image.image' => 'The selected file must be an image.',

            'image.mimes' => 'The image must be a JPG, JPEG, PNG, or WebP file.',

            'image.max' => 'The image may not be larger than 5 MB.',
        ];
    }
}
