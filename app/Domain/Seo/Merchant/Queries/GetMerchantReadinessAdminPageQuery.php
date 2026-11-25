<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Queries;

use App\Domain\Seo\Merchant\Data\MerchantReadinessAdminPageData;
use App\Domain\Seo\Merchant\Data\MerchantReadinessItemData;
use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;
use App\Domain\Seo\Merchant\Enums\MerchantListingFindingSeverity;
use App\Domain\Seo\Merchant\Enums\MerchantListingReadinessStatus;

final readonly class GetMerchantReadinessAdminPageQuery
{
    private const PER_PAGE = 50;

    public function __construct(
        private GetMerchantReadinessWorkspaceQuery $workspace,
    ) {}

    public function get(
        ?string $search = null,
        ?MerchantListingReadinessStatus $status = null,
        ?string $feed = null,
        ?string $availability = null,
        ?MerchantIdentifierState $identifierState = null,
        ?MerchantListingFindingSeverity $severity = null,
        int $page = 1,
    ): MerchantReadinessAdminPageData {
        $workspace =
            $this->workspace
                ->get();

        $normalizedSearch =
            trim(
                $search ?? '',
            );

        $filtered =
            array_values(
                array_filter(
                    $workspace->items,
                    fn (
                        MerchantReadinessItemData $item,
                    ): bool => $this->matches(
                        item: $item,

                        search: $normalizedSearch,

                        status: $status,

                        feed: $feed,

                        availability: $availability,

                        identifierState: $identifierState,

                        severity: $severity,
                    ),
                ),
            );

        usort(
            $filtered,
            fn (
                MerchantReadinessItemData $left,
                MerchantReadinessItemData $right,
            ): int => $this->compare(
                $left,
                $right,
            ),
        );

        $total =
            count(
                $filtered,
            );

        $lastPage =
            max(
                1,
                (int) ceil(
                    $total /
                    self::PER_PAGE,
                ),
            );

        $currentPage =
            min(
                max(
                    1,
                    $page,
                ),
                $lastPage,
            );

        $offset =
            ($currentPage - 1)
            * self::PER_PAGE;

        /** @var list<MerchantReadinessItemData> $items */
        $items =
            array_slice(
                $filtered,
                $offset,
                self::PER_PAGE,
            );

        return new MerchantReadinessAdminPageData(
            policyVersion: $workspace->policyVersion,

            summary: $workspace->summary,

            policy: $workspace->policy,

            filters: [
                'search' => $normalizedSearch,

                'status' => $status?->value,

                'feed' => $this->normalizeFeed(
                    $feed,
                ),

                'availability' => $availability,

                'identifier_state' => $identifierState?->value,

                'severity' => $severity?->value,
            ],

            options: $this->options(
                $workspace->items,
            ),

            items: $items,

            pagination: [
                'current_page' => $currentPage,

                'per_page' => self::PER_PAGE,

                'total' => $total,

                'last_page' => $lastPage,

                'from' => $total === 0
                        ? null
                        : $offset + 1,

                'to' => $total === 0
                        ? null
                        : $offset
                            + count(
                                $items,
                            ),

                'previous_page' => $currentPage > 1
                        ? $currentPage - 1
                        : null,

                'next_page' => $currentPage < $lastPage
                        ? $currentPage + 1
                        : null,
            ],
        );
    }

    private function matches(
        MerchantReadinessItemData $item,
        string $search,
        ?MerchantListingReadinessStatus $status,
        ?string $feed,
        ?string $availability,
        ?MerchantIdentifierState $identifierState,
        ?MerchantListingFindingSeverity $severity,
    ): bool {
        if (
            $search !== ''
            && ! $this->matchesSearch(
                $item,
                $search,
            )
        ) {
            return false;
        }

        if (
            $status !== null
            && $item->status !== $status
        ) {
            return false;
        }

        $feed =
            $this->normalizeFeed(
                $feed,
            );

        if (
            $feed === 'eligible'
            && ! $item->feedEligible
        ) {
            return false;
        }

        if (
            $feed === 'excluded'
            && $item->feedEligible
        ) {
            return false;
        }

        if (
            $availability !== null
            && $availability !== ''
            && $item->availability !== $availability
        ) {
            return false;
        }

        if (
            $identifierState !== null
            && $item->identifierState !==
                $identifierState->value
        ) {
            return false;
        }

        if (
            $severity !== null
            && ! $this->hasSeverity(
                $item,
                $severity,
            )
        ) {
            return false;
        }

        return true;
    }

    private function matchesSearch(
        MerchantReadinessItemData $item,
        string $search,
    ): bool {
        $needle =
            mb_strtolower(
                $search,
            );

        $values = [
            $item->productName,
            $item->variantLabel,
            $item->feedId,
            (string) $item->productId,
            $item->variantId === null
                ? null
                : (string) $item->variantId,
        ];

        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }

            if (
                str_contains(
                    mb_strtolower(
                        $value,
                    ),
                    $needle,
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function hasSeverity(
        MerchantReadinessItemData $item,
        MerchantListingFindingSeverity $severity,
    ): bool {
        foreach (
            $item->findings as $finding
        ) {
            if (
                $finding->severity ===
                $severity
            ) {
                return true;
            }
        }

        return false;
    }

    private function compare(
        MerchantReadinessItemData $left,
        MerchantReadinessItemData $right,
    ): int {
        $status =
            $this->statusRank(
                $left->status,
            ) <=>
            $this->statusRank(
                $right->status,
            );

        if ($status !== 0) {
            return $status;
        }

        $name =
            strnatcasecmp(
                $left->productName,
                $right->productName,
            );

        if ($name !== 0) {
            return $name;
        }

        $product =
            $left->productId <=>
            $right->productId;

        if ($product !== 0) {
            return $product;
        }

        return
            ($left->variantId ?? 0)
            <=>
            ($right->variantId ?? 0);
    }

    private function statusRank(
        MerchantListingReadinessStatus $status,
    ): int {
        return match ($status) {
            MerchantListingReadinessStatus::Blocked => 0,

            MerchantListingReadinessStatus::NeedsWork => 1,

            MerchantListingReadinessStatus::Ready => 2,
        };
    }

    private function normalizeFeed(
        ?string $feed,
    ): ?string {
        return in_array(
            $feed,
            [
                'eligible',
                'excluded',
            ],
            true,
        )
            ? $feed
            : null;
    }

    /**
     * @param  list<MerchantReadinessItemData>  $items
     * @return array{
     *     statuses: list<array{value: string, label: string}>,
     *     feed_states: list<array{value: string, label: string}>,
     *     availabilities: list<array{value: string, label: string}>,
     *     identifier_states: list<array{value: string, label: string}>,
     *     severities: list<array{value: string, label: string}>
     * }
     */
    private function options(
        array $items,
    ): array {
        /** @var array<string, true> $availabilityValues */
        $availabilityValues = [];

        foreach ($items as $item) {
            if (
                $item->availability === null
                || $item->availability === ''
            ) {
                continue;
            }

            $availabilityValues[
                $item->availability
            ] = true;
        }

        $availabilities =
            array_keys(
                $availabilityValues,
            );

        sort(
            $availabilities,
            SORT_STRING,
        );

        return [
            'statuses' => array_map(
                static fn (
                    MerchantListingReadinessStatus $value,
                ): array => [
                    'value' => $value->value,

                    'label' => match ($value) {
                        MerchantListingReadinessStatus::Ready => 'Ready',

                        MerchantListingReadinessStatus::NeedsWork => 'Needs work',

                        MerchantListingReadinessStatus::Blocked => 'Blocked',
                    },
                ],
                MerchantListingReadinessStatus::cases(),
            ),

            'feed_states' => [
            [
                'value' => 'eligible',

                'label' => 'Feed eligible',
            ],
            [
                'value' => 'excluded',

                'label' => 'Excluded',
            ],
            ],

            'availabilities' => array_map(
                static fn (
                    string $value,
                ): array => [
                    'value' => $value,

                    'label' => self::label(
                        $value,
                    ),
                ],
                $availabilities,
            ),

            'identifier_states' => array_map(
                static fn (
                    MerchantIdentifierState $value,
                ): array => [
                    'value' => $value->value,

                    'label' => match ($value) {
                        MerchantIdentifierState::Available => 'Identifiers available',

                        MerchantIdentifierState::NotAvailableDeclared => 'Not available declared',

                        MerchantIdentifierState::Unknown => 'Unknown / review required',
                    },
                ],
                MerchantIdentifierState::cases(),
            ),

            'severities' => array_map(
                static fn (
                    MerchantListingFindingSeverity $value,
                ): array => [
                    'value' => $value->value,

                    'label' => match ($value) {
                        MerchantListingFindingSeverity::Blocker => 'Blocker',

                        MerchantListingFindingSeverity::Warning => 'Warning',

                        MerchantListingFindingSeverity::Info => 'Info',
                    },
                ],
                MerchantListingFindingSeverity::cases(),
            ),
        ];
    }

    private static function label(
        string $value,
    ): string {
        return ucfirst(
            str_replace(
                '_',
                ' ',
                $value,
            ),
        );
    }
}
