<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

final readonly class MerchantReadinessAdminPageData
{
    /**
     * @param array{
     *     search: string,
     *     status: string|null,
     *     feed: string|null,
     *     availability: string|null,
     *     identifier_state: string|null,
     *     severity: string|null
     * } $filters
     * @param array{
     *     statuses: list<array{value: string, label: string}>,
     *     feed_states: list<array{value: string, label: string}>,
     *     availabilities: list<array{value: string, label: string}>,
     *     identifier_states: list<array{value: string, label: string}>,
     *     severities: list<array{value: string, label: string}>
     * } $options
     * @param  list<MerchantReadinessItemData>  $items
     * @param array{
     *     current_page: int,
     *     per_page: int,
     *     total: int,
     *     last_page: int,
     *     from: int|null,
     *     to: int|null,
     *     previous_page: int|null,
     *     next_page: int|null
     * } $pagination
     */
    public function __construct(
        public string $policyVersion,
        public MerchantReadinessSummaryData $summary,
        public MerchantPolicyReadinessData $policy,
        public array $filters,
        public array $options,
        public array $items,
        public array $pagination,
    ) {}

    /**
     * @return array{
     *     policy_version: string,
     *     summary: array<string, mixed>,
     *     policy: array<string, mixed>,
     *     filters: array<string, string|null>,
     *     options: array<string, list<array{value: string, label: string}>>,
     *     items: list<array<string, mixed>>,
     *     pagination: array<string, int|null>
     * }
     */
    public function toArray(): array
    {
        return [
            'policy_version' => $this->policyVersion,

            'summary' => $this->summary
                ->toArray(),

            'policy' => $this->policy
                ->toArray(),

            'filters' => $this->filters,

            'options' => $this->options,

            'items' => array_map(
                static fn (
                    MerchantReadinessItemData $item,
                ): array => $item->toArray(),
                $this->items,
            ),

            'pagination' => $this->pagination,
        ];
    }
}
