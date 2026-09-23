<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\PageBuilder\Data\CreatePageData;
use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use App\Models\Page;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can(
            'create',
            Page::class,
        ) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::enum(PageType::class),
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                Rule::enum(PageStatus::class),
            ],

            'content_mode' => [
                'required',
                Rule::enum(PageContentMode::class),
            ],

            'content' => [
                'nullable',
                'string',
            ],

            'meta_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'meta_description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'published_at' => [
                'nullable',
                'date',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->normalizeRequiredString(
                $this->input('title'),
            ),

            'slug' => $this->normalizeNullableString(
                $this->input('slug'),
            ),

            'content' => $this->normalizeNullableContent(
                $this->input('content'),
            ),

            'meta_title' => $this->normalizeNullableString(
                $this->input('meta_title'),
            ),

            'meta_description' => $this->normalizeNullableString(
                $this->input('meta_description'),
            ),

            'published_at' => $this->normalizeNullableString(
                $this->input('published_at'),
            ),
        ]);
    }

    public function toData(): CreatePageData
    {
        $validated = $this->validated();

        return new CreatePageData(
            type: PageType::from(
                (string) $validated['type'],
            ),

            title: (string) $validated['title'],

            slug: isset($validated['slug'])
                ? (string) $validated['slug']
                : null,

            status: PageStatus::from(
                (string) $validated['status'],
            ),

            contentMode: PageContentMode::from(
                (string) $validated['content_mode'],
            ),

            content: isset($validated['content'])
                ? (string) $validated['content']
                : null,

            metaTitle: isset($validated['meta_title'])
                ? (string) $validated['meta_title']
                : null,

            metaDescription: isset($validated['meta_description'])
                ? (string) $validated['meta_description']
                : null,

            publishedAt: isset($validated['published_at'])
                ? CarbonImmutable::parse(
                    (string) $validated['published_at'],
                )
                : null,
        );
    }

    private function normalizeRequiredString(
        mixed $value,
    ): mixed {
        return is_string($value)
            ? trim($value)
            : $value;
    }

    private function normalizeNullableString(
        mixed $value,
    ): mixed {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }

    private function normalizeNullableContent(
        mixed $value,
    ): mixed {
        if (! is_string($value)) {
            return $value;
        }

        return trim($value) === ''
            ? null
            : $value;
    }
}
