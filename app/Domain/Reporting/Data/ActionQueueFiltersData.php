<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

use App\Domain\Reporting\Enums\ActionQueueSeverity;
use App\Domain\Reporting\Enums\ActionQueueSource;

final readonly class ActionQueueFiltersData
{
    /**
     * @param  array<int, string>  $sources
     * @param  array<int, string>  $severities
     */
    public function __construct(
        public array $sources = [],
        public array $severities = [],
        public ?bool $requiresAction = null,
        public ?string $search = null,
        public int $perPage = 25,
    ) {}

    /**
     * @return array<int, string>
     */
    public function sourceValues(): array
    {
        $allowed =
            array_map(
                static fn (
                    ActionQueueSource $source,
                ): string => $source->value,

                ActionQueueSource::cases(),
            );

        return array_values(
            array_filter(
                $this->normalizeStrings(
                    $this->sources,
                ),
                static fn (
                    string $value,
                ): bool => in_array(
                    $value,
                    $allowed,
                    true,
                ),
            ),
        );
    }

    /**
     * @return array<int, string>
     */
    public function severityValues(): array
    {
        $allowed =
            array_map(
                static fn (
                    ActionQueueSeverity $severity,
                ): string => $severity->value,

                ActionQueueSeverity::cases(),
            );

        return array_values(
            array_filter(
                $this->normalizeStrings(
                    $this->severities,
                ),
                static fn (
                    string $value,
                ): bool => in_array(
                    $value,
                    $allowed,
                    true,
                ),
            ),
        );
    }

    public function normalizedSearch(): ?string
    {
        $search =
            trim(
                (string) $this->search,
            );

        return $search !== ''
            ? $search
            : null;
    }

    public function normalizedPerPage(): int
    {
        return in_array(
            $this->perPage,
            [
                10,
                25,
                50,
                100,
            ],
            true,
        )
            ? $this->perPage
            : 25;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sources' => $this->sourceValues(),

            'severities' => $this->severityValues(),

            'requires_action' => $this->requiresAction,

            'search' => $this->normalizedSearch(),

            'per_page' => $this->normalizedPerPage(),
        ];
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, string>
     */
    private function normalizeStrings(
        array $values,
    ): array {
        $normalized = [];

        foreach (
            $values as $value
        ) {
            $value =
                trim(
                    $value,
                );

            if (
                $value !== '' &&
                ! in_array(
                    $value,
                    $normalized,
                    true,
                )
            ) {
                $normalized[] =
                    $value;
            }
        }

        return $normalized;
    }
}
