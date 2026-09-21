<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

final readonly class SectionTemplateData
{
    public function __construct(
        public string $key,
        public string $label,
    ) {}

    /**
     * @return array{
     *     key: string,
     *     label: string
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
        ];
    }
}
