<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

use App\Domain\PageBuilder\Enums\SectionType;

final readonly class PageSectionData
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public ?int $id,
        public SectionType $type,
        public string $template,
        public array $config,
        public SectionLayoutData $layout,
        public int $position,
        public bool $isEnabled,
    ) {}

    /**
     * @return array{
     *     id: int|null,
     *     type: string,
     *     template: string,
     *     config: array<string, mixed>,
     *     layout: array{
     *         width: string
     *     },
     *     position: int,
     *     is_enabled: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'template' => $this->template,
            'config' => $this->config,
            'layout' => $this->layout->toArray(),
            'position' => $this->position,
            'is_enabled' => $this->isEnabled,
        ];
    }
}
