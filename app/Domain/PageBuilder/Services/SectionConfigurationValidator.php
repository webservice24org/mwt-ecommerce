<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Services;

use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;

final readonly class SectionConfigurationValidator
{
    public function __construct(
        private SectionRegistry $registry,
        private CatalogSourceReferenceValidator $catalogReferences,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function validate(
        SectionType $type,
        string $template,
        array $config,
    ): array {
        if (! $this->registry->has($type)) {
            throw new InvalidSectionConfiguration([
                'type' => [
                    'This section type is not registered.',
                ],
            ]);
        }

        if (
            ! $this->registry->supportsTemplate(
                $type,
                $template,
            )
        ) {
            throw new InvalidSectionConfiguration([
                'template' => [
                    'This template is not supported for the selected section type.',
                ],
            ]);
        }

        /*
         * Phase 1:
         * Pure structural/schema validation and normalization.
         */
        $normalizedConfig = $this->registry
            ->get($type)
            ->configSchema()
            ->validate($config);

        /*
         * Phase 2:
         * Validate references to external domains after the
         * configuration has been structurally normalized.
         */
        $this->catalogReferences->validate(
            sectionType: $type,
            config: $normalizedConfig,
        );

        return $normalizedConfig;
    }
}
