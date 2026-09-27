<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

use App\Domain\PageBuilder\Enums\SectionType;

final readonly class ResolvedPageSectionData
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public int $id,
        public SectionType $type,
        public string $template,
        public array $config,
        public array $data,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     type: string,
     *     template: string,
     *     config: array<string, mixed>,
     *     data: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'template' => $this->template,
            'config' => $this->config,
            'data' => $this->data,
        ];
    }
}
