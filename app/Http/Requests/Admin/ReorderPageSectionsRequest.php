<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\PageBuilder\Data\ReorderPageSectionsData;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class ReorderPageSectionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $page = $this->route('page');

        return $page instanceof Page
            && ($this->user('admin')?->can('update', $page) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'section_ids' => [
                'required',
                'array',
            ],

            'section_ids.*' => [
                'required',
                'integer',
                'distinct',
                'min:1',
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $page = $this->route('page');

                if (! $page instanceof Page) {
                    return;
                }

                $sectionIds = $this->input(
                    'section_ids',
                    [],
                );

                if (! is_array($sectionIds)) {
                    return;
                }

                $requestedIds = array_map(
                    static fn (mixed $id): int => (int) $id,
                    $sectionIds,
                );

                sort($requestedIds);

                $existingIds = PageSection::query()
                    ->where(
                        'page_id',
                        $page->id,
                    )
                    ->pluck('id')
                    ->map(
                        static fn (mixed $id): int => (int) $id,
                    )
                    ->sort()
                    ->values()
                    ->all();

                if ($requestedIds !== $existingIds) {
                    $validator->errors()->add(
                        'section_ids',
                        'The section order must contain every section belonging to this page exactly once.',
                    );
                }
            },
        ];
    }

    public function toData(): ReorderPageSectionsData
    {
        $validated = $this->validated();

        /** @var list<int> $sectionIds */
        $sectionIds = array_map(
            static fn (mixed $id): int => (int) $id,
            $validated['section_ids'],
        );

        return new ReorderPageSectionsData(
            sectionIds: $sectionIds,
        );
    }
}
