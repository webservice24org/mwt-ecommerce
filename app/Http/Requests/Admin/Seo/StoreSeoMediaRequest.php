<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Seo;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSeoMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user(
            'admin',
        ) !== null;
    }

    /**
     * @return array<string, mixed>
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
                'dimensions:min_width=300,min_height=157,max_width=5000,max_height=5000',
            ],
        ];
    }
}
