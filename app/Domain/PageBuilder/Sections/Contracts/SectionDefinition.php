<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections\Contracts;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;

interface SectionDefinition
{
    public function type(): SectionType;

    public function label(): string;

    /**
     * @return array<int, SectionTemplateData>
     */
    public function templates(): array;

    public function defaultTemplate(): string;

    /**
     * @return array<string, mixed>
     */
    public function defaultConfig(): array;

    /**
     * @return array<string, mixed>
     */
    public function defaultConfigForTemplate(
        string $template,
    ): array;

    public function configSchema(): SectionConfigSchema;
}
