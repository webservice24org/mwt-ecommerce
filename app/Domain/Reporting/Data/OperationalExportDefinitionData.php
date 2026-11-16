<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

use App\Domain\Reporting\Enums\OperationalExportType;

final readonly class OperationalExportDefinitionData
{
    /**
     * @param  array<int, string>  $headers
     */
    public function __construct(
        public OperationalExportType $type,
        public string $routeName,
        public array $headers,
        public int $maxRows,
    ) {}

    public function label(): string
    {
        return $this
            ->type
            ->label();
    }

    public function filename(
        ?string $timestamp = null,
    ): string {
        $suffix =
            $timestamp ??
            now()->format(
                'Y-m-d-His',
            );

        return $this
            ->type
            ->filenamePrefix().
            '-'.
            $suffix.
            '.csv';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this
                ->type
                ->value,

            'label' => $this->label(),

            'route_name' => $this->routeName,

            'headers' => $this->headers,

            'max_rows' => $this->maxRows,
        ];
    }
}
