<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Data;

final readonly class InventoryOperationsReportFiltersData
{
    /**
     * @param  array<int, string>  $statuses
     * @param  array<int, string>  $stockStates
     */
    public function __construct(
        public array $statuses = [],
        public array $stockStates = [],
        public ?string $search = null,
        public int $perPage = 25,
    ) {}

    /**
     * @return array<int, string>
     */
    public function statusValues(): array
    {
        return $this->normalizeStrings(
            $this->statuses,
        );
    }

    /**
     * @return array<int, string>
     */
    public function stockStateValues(): array
    {
        return array_values(
            array_filter(
                $this->normalizeStrings(
                    $this->stockStates,
                ),
                static fn (
                    string $value,
                ): bool => in_array(
                    $value,
                    [
                        'in_stock',
                        'low_stock',
                        'out_of_stock',
                        'negative_stock',
                    ],
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
            'statuses' => $this->statusValues(),

            'stock_states' => $this->stockStateValues(),

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
