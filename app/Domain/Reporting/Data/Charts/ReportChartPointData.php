<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data\Charts;

final readonly class ReportChartPointData
{
    /**
     * @param  array<string, int|float|string|null>  $values
     */
    public function __construct(
        public string $key,
        public string $label,
        public array $values,
    ) {}

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     values: array<string, int|float|string|null>
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,

            'label' => $this->label,

            'values' => $this->values,
        ];
    }
}
