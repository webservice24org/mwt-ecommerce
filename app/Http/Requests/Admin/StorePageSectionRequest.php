<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\PageBuilder\Data\CreatePageSectionData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePageSectionRequest extends FormRequest
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
            'type' => [
                'required',
                'string',
                Rule::enum(SectionType::class),
            ],

            'template' => [
                'required',
                'string',
                'max:100',
            ],

            'config' => [
                'required',
                'array',
            ],

            'is_enabled' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function toData(): CreatePageSectionData
    {
        $validated = $this->validated();

        /** @var array<string, mixed> $config */
        $config = $validated['config'];

        return new CreatePageSectionData(
            type: SectionType::from(
                (string) $validated['type'],
            ),
            template: (string) $validated['template'],
            config: $config,
            isEnabled: (bool) ($validated['is_enabled'] ?? true),
        );
    }
}
