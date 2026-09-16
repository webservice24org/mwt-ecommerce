<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read array<int, int|string> $image_ids
 */
final class ReorderProductImagesRequest extends FormRequest
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
            'image_ids' => [
                'required',
                'array',
            ],

            'image_ids.*' => [
                'required',
                'integer',
                'distinct',
            ],
        ];
    }

    /**
     * @return list<int>
     */
    public function imageIds(): array
    {
        /** @var array<int, mixed> $ids */
        $ids = $this->validated(
            'image_ids',
        );

        return collect($ids)
            ->map(
                static fn (
                    mixed $id,
                ): int => (int) $id,
            )
            ->values()
            ->all();
    }
}
