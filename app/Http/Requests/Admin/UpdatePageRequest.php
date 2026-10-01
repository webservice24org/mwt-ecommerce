<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\PageBuilder\Data\UpdatePageData;
use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use App\Models\Page;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Domain\PageBuilder\Enums\PageLayout;

final class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $page = $this->route('page');

        return $page instanceof Page
            && (
                $this->user('admin')?->can(
                    'update',
                    $page,
                ) ?? false
            );
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

            'layout' => [
                'sometimes',
                'string',
                Rule::enum(
                    PageLayout::class,
                ),
            ],

            'show_breadcrumbs' => [
                'sometimes',
                'boolean',
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

    public function toData(): UpdatePageData
    {
        $validated = $this->validated();
        $page = $this->route('page');

        if (! $page instanceof Page) {
            abort(404);
        }

        return new UpdatePageData(
            type: PageType::from(
                (string) $validated['type'],
            ),

            layout: isset($validated['layout'])
                ? PageLayout::from(
                    (string) $validated['layout'],
                )
                : null,

            showBreadcrumbs: array_key_exists(
                'show_breadcrumbs',
                $validated,
            )
                ? (bool) $validated['show_breadcrumbs']
                : $page->show_breadcrumbs,
            

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
