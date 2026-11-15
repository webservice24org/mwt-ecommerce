<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data\Charts;

final readonly class ReportChartSeriesData
{
    public function __construct(
        public string $key,
        public string $label,
        public string $valueType,
    ) {}

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     value_type: string
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,

            'label' => $this->label,

            'value_type' => $this->valueType,
        ];
    }
}
