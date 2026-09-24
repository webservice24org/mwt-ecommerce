<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\PageBuilder\Data\UpdatePageSectionData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        ];
    }

    public function toData(): UpdatePageSectionData
    {
        /** @var array<string, mixed> $config */
        $config = $this->validated('config');

        return new UpdatePageSectionData(
            type: SectionType::from(
                $this->string('type')->toString(),
            ),
            template: $this->string('template')->toString(),
            config: $config,
            isEnabled: $this->boolean('is_enabled'),
        );
    }
}
