<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

final readonly class MerchantReadinessWorkspaceData
{
    /**
     * @param  list<MerchantReadinessItemData>  $items
     */
    public function __construct(
        public string $policyVersion,
        public MerchantReadinessSummaryData $summary,
        public MerchantPolicyReadinessData $policy,
        public array $items,
    ) {}

    /**
     * @return array{
     *     policy_version: string,
     *     summary: array<string, mixed>,
     *     policy: array<string, mixed>,
     *     items: list<array<string, mixed>>
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

            'items' => array_map(
                static fn (
                    MerchantReadinessItemData $item,
                ): array => $item->toArray(),
                $this->items,
            ),
        ];
    }
}
