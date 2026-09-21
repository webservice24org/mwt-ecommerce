<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections\Contracts;

interface SectionConfigSchema
{
    /**
     * Validate and normalize configuration.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function validate(array $config): array;
}
