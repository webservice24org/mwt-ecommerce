<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\PageBuilder\Data\UpdatePageSectionData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Domain\PageBuilder\Enums\SectionWidth;
use App\Domain\PageBuilder\Data\SectionLayoutData;


final class UpdatePageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $page = $this->route('page');

        return $page instanceof Page
            && $this->user('admin')?->can(
                'update',
                $page,
            ) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
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
                'required',
                'boolean',
            ],

            'layout' => [
                'sometimes',
                'array',
            ],

            'layout.width' => [
                'sometimes',
                'string',
                Rule::enum(
                    SectionWidth::class,
                ),
            ],
        ];
    }

    public function toData(): UpdatePageSectionData
    {
        $validated = $this->validated();

        /** @var array<string, mixed> $config */
        $config = $validated['config'];

        $layout = isset($validated['layout'])
            && is_array($validated['layout'])
                ? $validated['layout']
                : null;

        return new UpdatePageSectionData(
            type: SectionType::from(
                (string) $validated['type'],
            ),
            template:
                (string) $validated['template'],
            config: $config,
            layout:
                SectionLayoutData::fromArray(
                    $layout,
                ),
            isEnabled:
                (bool) $validated[
                    'is_enabled'
                ],
        );
    }

}
