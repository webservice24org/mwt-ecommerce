<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;

final readonly class SectionDefinitionData
{
    /**
     * @param  list<SectionTemplateData>  $templates
     * @param  array<string, mixed>  $defaultConfig
     */
    public function __construct(
        public string $type,
        public string $label,
        public array $templates,
        public string $defaultTemplate,
        public array $defaultConfig,
    ) {}

    public static function fromDefinition(
        SectionDefinition $definition,
    ): self {
        return new self(
            type: $definition->type()->value,
            label: $definition->label(),
            templates: $definition->templates(),
            defaultTemplate: $definition->defaultTemplate(),
            defaultConfig: $definition->defaultConfig(),
        );
    }

    /**
     * @return array{
     *     type: string,
     *     label: string,
     *     templates: list<array{
     *         key: string,
     *         label: string
     *     }>,
     *     default_template: string,
     *     default_config: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'label' => $this->label,
            'templates' => array_map(
                static fn (SectionTemplateData $template): array => $template->toArray(),
                $this->templates,
            ),
            'default_template' => $this->defaultTemplate,
            'default_config' => $this->defaultConfig,
        ];
    }
}
