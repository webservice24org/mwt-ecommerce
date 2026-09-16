<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use App\Domain\Catalog\Data\SaveProductVideoData;
use App\Domain\Catalog\Enums\ProductVideoType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

final class SaveProductVideoRequest extends FormRequest
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
            'type' => [
                'required',
                Rule::enum(ProductVideoType::class),
            ],

            'video' => [
                'nullable',
                'file',
                'mimes:mp4,webm',
                'max:102400',
            ],

            'url' => [
                'nullable',
                'string',
                'url',
                'max:2048',
            ],

            'title' => [
                'nullable',
                'string',
                'max:180',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (
            [
                'url',
                'title',
            ] as $key
        ) {
            if (! $this->has($key)) {
                continue;
            }

            $value = $this->input($key);

            $this->merge([
                $key => is_string($value)
                        ? (
                            trim($value) === ''
                                ? null
                                : trim($value)
                        )
                        : null,
            ]);
        }
    }

    public function toData(): SaveProductVideoData
    {
        $type = ProductVideoType::from(
            (string) $this->validated(
                'type',
            ),
        );

        $video = $this->file('video');

        return new SaveProductVideoData(
            type: $type,
            video: $video instanceof UploadedFile
                    ? $video
                    : null,
            url: $this->validated('url'),
            title: $this->validated('title'),
        );
    }
}
