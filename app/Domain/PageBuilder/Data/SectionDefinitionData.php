<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;

final readonly class SectionDefinitionData
{
    /**
     * @param  list<array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     category: string
     * }>  $templates
     * @param  array<string, mixed>  $defaultConfig
     * @param  array<string, array<string, mixed>>  $templateDefaultConfigs
     */
    public function __construct(
        public string $type,
        public string $label,
        public array $templates,
        public string $defaultTemplate,
        public array $defaultConfig,
        public array $templateDefaultConfigs,
    ) {}

    public static function fromDefinition(
        SectionDefinition $definition,
    ): self {
        $templates = array_map(
            static fn ($template): array => $template->toArray(),
            $definition->templates(),
        );

        $templateDefaultConfigs = [];

        foreach ($definition->templates() as $template) {
            $templateDefaultConfigs[$template->key] =
                $definition->defaultConfigForTemplate(
                    $template->key,
                );
        }

        return new self(
            type: $definition->type()->value,
            label: $definition->label(),
            templates: $templates,
            defaultTemplate: $definition->defaultTemplate(),
            defaultConfig: $definition->defaultConfig(),
            templateDefaultConfigs: $templateDefaultConfigs,
        );
    }

    /**
     * @return array{
     *     type: string,
     *     label: string,
     *     templates: list<array{
     *         key: string,
     *         label: string,
     *         description: string,
     *         category: string
     *     }>,
     *     default_template: string,
     *     default_config: array<string, mixed>,
     *     template_default_configs: array<string, array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'label' => $this->label,
            'templates' => $this->templates,
            'default_template' => $this->defaultTemplate,
            'default_config' => $this->defaultConfig,
            'template_default_configs' => $this->templateDefaultConfigs,
        ];
    }
}
