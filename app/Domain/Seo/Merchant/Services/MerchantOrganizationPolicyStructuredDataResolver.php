<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Services;

use App\Domain\Seo\Merchant\Data\MerchantStructuredDataPolicyData;
use App\Domain\Seo\Merchant\Enums\MerchantReturnFees;
use App\Domain\Seo\Merchant\Enums\MerchantReturnPolicyCategory;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Shipping\Enums\ShippingZoneRuleType;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Collection;

final readonly class MerchantOrganizationPolicyStructuredDataResolver
{
    public function __construct(
        private SeoSettingsService $seoSettings,
        private MerchantStructuredDataPolicyService $policies,
    ) {}

    /**
     * @return array{
     *     '@context': string,
     *     '@graph': list<array<string, mixed>>
     * }|null
     */
    public function resolve(): ?array
    {
        $settings =
            $this
                ->seoSettings
                ->get();

        if (! $settings->enableStructuredData) {
            return null;
        }

        $baseUrl =
            $this->baseUrl(
                $settings
                    ->canonicalHost,
            );

        if ($baseUrl === null) {
            return null;
        }

        $policy =
            $this
                ->policies
                ->get();

        $organization = [
            '@type' => 'Organization',

            '@id' => $baseUrl
                .'#organization',

            'url' => $baseUrl,

            'name' => $this->organizationName(
                $settings
                    ->organizationName,
                $settings
                    ->siteName,
            ),
        ];

        $shippingServices =
            $policy->shippingMarkupEnabled
                ? $this->shippingServices(
                    policy: $policy,

                    baseUrl: $baseUrl,
                )
                : [];

        if ($shippingServices !== []) {
            $organization[
                'hasShippingService'
            ] =
                count(
                    $shippingServices,
                ) === 1
                    ? $shippingServices[
                        0
                    ]
                    : $shippingServices;
        }

        $returnPolicy =
            $policy->returnPolicyEnabled
                ? $this->returnPolicy(
                    policy: $policy,

                    baseUrl: $baseUrl,
                )
                : null;

        if ($returnPolicy !== null) {
            $organization[
                'hasMerchantReturnPolicy'
            ] =
                $returnPolicy;
        }

        if (
            ! array_key_exists(
                'hasShippingService',
                $organization,
            )
            && ! array_key_exists(
                'hasMerchantReturnPolicy',
                $organization,
            )
        ) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',

            '@graph' => [
                $organization,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function shippingOmissionReasons(): array
    {
        $policy =
            $this
                ->policies
                ->get();

        if (! $policy->shippingMarkupEnabled) {
            return [];
        }

        $zones =
            $this->shippingZones();

        if ($zones->isEmpty()) {
            return [
                'No active checkout shipping zones are configured.',
            ];
        }

        $reasons = [];

        foreach ($zones as $zone) {
            $activeRates =
                $this->activeRates(
                    $zone,
                );

            if ($activeRates === []) {
                continue;
            }

            if (
                count(
                    $activeRates,
                ) > 1
            ) {
                $reasons[] =
                    "Zone {$zone->name} has multiple active rates. Checkout priority cannot be represented safely by Organization shipping markup.";
            }

            foreach (
                $zone->rules as $rule
            ) {
                if (
                    $rule->ruleType() !==
                    ShippingZoneRuleType::CountryCode
                ) {
                    $reasons[] =
                        "Zone {$zone->name} uses a non-country rule ({$rule->ruleType()->value}). Google DefinedRegion cannot safely represent this checkout rule.";
                }
            }

            if (
                $zone->is_catch_all
                && $policy->shippingCountryCodes ===
                    []
            ) {
                $reasons[] =
                    "Catch-all zone {$zone->name} requires explicit merchant shipping country codes before it can be published safely.";
            }
        }

        $coveredCountries = [];

        foreach ($zones as $zone) {
            $activeRates =
                $this->activeRates(
                    $zone,
                );

            if (
                count(
                    $activeRates,
                ) !== 1
            ) {
                continue;
            }

            $countries =
                $zone->is_catch_all
                    ? $policy
                        ->shippingCountryCodes
                    : $zone
                        ->rules
                        ->filter(
                            static fn (
                                $rule,
                            ): bool => $rule->ruleType() ===
                                ShippingZoneRuleType::CountryCode,
                        )
                        ->map(
                            static fn (
                                $rule,
                            ): string => strtoupper(
                                trim(
                                    (string) $rule
                                        ->value,
                                ),
                            ),
                        )
                        ->filter(
                            static fn (
                                string $country,
                            ): bool => preg_match(
                                '/^[A-Z]{2}$/',
                                $country,
                            ) === 1,
                        )
                        ->unique()
                        ->values()
                        ->all();

            foreach ($countries as $country) {
                if (
                    array_key_exists(
                        $country,
                        $coveredCountries,
                    )
                ) {
                    $reasons[] =
                        "Country {$country} is covered by more than one active checkout zone. Shipping markup is omitted to avoid ambiguous merchant rates.";

                    continue;
                }

                $coveredCountries[
                    $country
                ] =
                    $zone->id;
            }
        }

        return array_values(
            array_unique(
                $reasons,
            ),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function shippingServices(
        MerchantStructuredDataPolicyData $policy,
        string $baseUrl,
    ): array {
        /*
         * If any active checkout zone is not faithfully representable
         * by Google's current DefinedRegion contract, publish no
         * shipping policy rather than broadening or inventing coverage.
         */
        if (
            $this->shippingOmissionReasons() !==
            []
        ) {
            return [];
        }

        $services = [];

        foreach (
            $this->shippingZones() as $zone
        ) {
            $activeRates =
                $this->activeRates(
                    $zone,
                );

            if (
                count(
                    $activeRates,
                ) !== 1
            ) {
                continue;
            }

            $rate =
                $activeRates[
                    0
                ];

            $countries =
                $zone->is_catch_all
                    ? $policy
                        ->shippingCountryCodes
                    : $zone
                        ->rules
                        ->filter(
                            static fn (
                                $rule,
                            ): bool => $rule->ruleType() ===
                                ShippingZoneRuleType::CountryCode,
                        )
                        ->map(
                            static fn (
                                $rule,
                            ): string => strtoupper(
                                trim(
                                    (string) $rule
                                        ->value,
                                ),
                            ),
                        )
                        ->filter(
                            static fn (
                                string $country,
                            ): bool => preg_match(
                                '/^[A-Z]{2}$/',
                                $country,
                            ) === 1,
                        )
                        ->unique()
                        ->values()
                        ->all();

            if ($countries === []) {
                continue;
            }

            $conditions =
                $this->shippingConditions(
                    rate: $rate,

                    countries: $countries,
                );

            if ($conditions === []) {
                continue;
            }

            $services[] = [
                '@type' => 'ShippingService',

                '@id' => $baseUrl
                    .'#shipping-service-'
                    .$zone->id,

                'name' => $rate
                    ->shippingMethod
                    ->name,

                'fulfillmentType' => 'https://schema.org/FulfillmentTypeDelivery',

                'shippingConditions' => count(
                    $conditions,
                ) === 1
                        ? $conditions[
                            0
                        ]
                        : $conditions,
            ];
        }

        return $services;
    }

    /**
     * @param  list<string>  $countries
     * @return list<array<string, mixed>>
     */
    private function shippingConditions(
        ShippingRate $rate,
        array $countries,
    ): array {
        $destinations =
            array_map(
                static fn (
                    string $country,
                ): array => [
                    '@type' => 'DefinedRegion',

                    'addressCountry' => $country,
                ],
                $countries,
            );

        $minimum =
            $rate
                ->min_order_amount;

        $maximum =
            $rate
                ->max_order_amount;

        $threshold =
            $rate
                ->free_shipping_threshold;

        if (
            $threshold === null
        ) {
            return [
                $this->shippingCondition(
                    destinations: $destinations,

                    minimum: $minimum,

                    maximum: $maximum,

                    amount: $rate
                        ->amount,
                ),
            ];
        }

        $conditions = [];

        $paidMaximum =
            $threshold
            - 1;

        if (
            $maximum !== null
        ) {
            $paidMaximum =
                min(
                    $paidMaximum,
                    $maximum,
                );
        }

        $paidMinimum =
            $minimum
            ?? 0;

        if (
            $paidMaximum >=
            $paidMinimum
        ) {
            $conditions[] =
                $this->shippingCondition(
                    destinations: $destinations,

                    minimum: $paidMinimum,

                    maximum: $paidMaximum,

                    amount: $rate
                        ->amount,
                );
        }

        $freeMinimum =
            max(
                $threshold,
                $minimum
                ?? 0,
            );

        if (
            $maximum === null
            || $freeMinimum <=
                $maximum
        ) {
            $conditions[] =
                $this->shippingCondition(
                    destinations: $destinations,

                    minimum: $freeMinimum,

                    maximum: $maximum,

                    amount: 0,
                );
        }

        return $conditions;
    }

    /**
     * @param  list<array<string, string>>  $destinations
     * @return array<string, mixed>
     */
    private function shippingCondition(
        array $destinations,
        ?int $minimum,
        ?int $maximum,
        int $amount,
    ): array {
        $currency =
            strtoupper(
                (string) config(
                    'commerce.currency',
                    'BDT',
                ),
            );

        $condition = [
            '@type' => 'ShippingConditions',

            'shippingDestination' => count(
                $destinations,
            ) === 1
                    ? $destinations[
                        0
                    ]
                    : $destinations,

            'shippingRate' => [
            '@type' => 'MonetaryAmount',

            'value' => $this->money(
                $amount,
            ),

            'currency' => $currency,
            ],
        ];

        if (
            $minimum !== null
            || $maximum !== null
        ) {
            $orderValue = [
                '@type' => 'MonetaryAmount',

                'currency' => $currency,
            ];

            if ($minimum !== null) {
                $orderValue[
                    'minValue'
                ] =
                    $this->money(
                        $minimum,
                    );
            }

            if ($maximum !== null) {
                $orderValue[
                    'maxValue'
                ] =
                    $this->money(
                        $maximum,
                    );
            }

            $condition[
                'orderValue'
            ] =
                $orderValue;
        }

        return $condition;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function returnPolicy(
        MerchantStructuredDataPolicyData $policy,
        string $baseUrl,
    ): ?array {
        $category =
            $policy
                ->returnPolicyCategory;

        if (
            $category === null
            || $policy
                ->returnApplicableCountryCodes ===
                []
            || $policy
                ->returnCountryCode ===
                null
            || preg_match(
                '/^[A-Z]{2}$/',
                strtoupper(
                    $policy
                        ->returnCountryCode,
                ),
            ) !== 1
        ) {
            return null;
        }

        $returnsPermitted =
            $category !==
            MerchantReturnPolicyCategory::NotPermitted;

        if (
            $category ===
                MerchantReturnPolicyCategory::Finite
            && (
                $policy
                    ->returnDays ===
                    null
                || $policy
                    ->returnDays <=
                    0
            )
        ) {
            return null;
        }

        $returnPolicy = [
            '@type' => 'MerchantReturnPolicy',

            '@id' => $baseUrl
                .'#merchant-return-policy',

            'applicableCountry' => count(
                $policy
                    ->returnApplicableCountryCodes,
            ) === 1
                    ? $policy
                        ->returnApplicableCountryCodes[
                            0
                        ]
                    : $policy
                        ->returnApplicableCountryCodes,

            'returnPolicyCategory' => $category
                ->schemaUrl(),
        ];

        if (
            $category ===
            MerchantReturnPolicyCategory::Finite
        ) {
            $returnPolicy[
                'merchantReturnDays'
            ] =
                $policy
                    ->returnDays;
        }

        if (
            $policy
                ->returnPolicyUrl !==
                null
            && $this->isHttpUrl(
                $policy
                    ->returnPolicyUrl,
            )
        ) {
            $returnPolicy[
                'merchantReturnLink'
            ] =
                $policy
                    ->returnPolicyUrl;
        }

        $returnPolicy[
            'returnPolicyCountry'
        ] =
            strtoupper(
                $policy
                    ->returnCountryCode,
            );

        if (
            $returnsPermitted
            && $policy
                ->returnMethod !==
                null
        ) {
            $returnPolicy[
                'returnMethod'
            ] =
                $policy
                    ->returnMethod
                    ->schemaUrl();
        }

        if (
            $returnsPermitted
            && $policy
                ->returnFees !==
                null
        ) {
            $returnPolicy[
                'returnFees'
            ] =
                $policy
                    ->returnFees
                    ->schemaUrl();
        }

        if (
            $returnsPermitted
            && $policy
                ->returnFees ===
                MerchantReturnFees::ShippingFee
            && $policy
                ->returnShippingFeeAmount !==
                null
            && $policy
                ->returnShippingFeeAmount >
                0
        ) {
            $returnPolicy[
                'returnShippingFeesAmount'
            ] = [
                '@type' => 'MonetaryAmount',

                'value' => $this->money(
                    $policy
                        ->returnShippingFeeAmount,
                ),

                'currency' => strtoupper(
                    (string) config(
                        'commerce.currency',
                        'BDT',
                    ),
                ),
            ];
        }

        if (
            $returnsPermitted
            && $policy
                ->refundType !==
                null
        ) {
            $returnPolicy[
                'refundType'
            ] =
                $policy
                    ->refundType
                    ->schemaUrl();
        }

        return $returnPolicy;
    }

    /**
     * @return Collection<int, ShippingZone>
     */
    private function shippingZones(): Collection
    {
        return ShippingZone::query()
            ->where(
                'is_active',
                true,
            )
            ->with([
                'rules',
                'rates.shippingMethod',
            ])
            ->orderBy(
                'priority',
            )
            ->orderBy(
                'id',
            )
            ->get();
    }

    /**
     * @return list<ShippingRate>
     */
    private function activeRates(
        ShippingZone $zone,
    ): array {
        return $zone
            ->rates
            ->filter(
                static fn (
                    ShippingRate $rate,
                ): bool => $rate
                    ->is_active
                    && $rate
                        ->shippingMethod
                        ?->is_active ===
                        true,
            )
            ->sort(
                static function (
                    ShippingRate $left,
                    ShippingRate $right,
                ): int {
                    $priority =
                        $left
                            ->priority
                        <=>
                        $right
                            ->priority;

                    if ($priority !== 0) {
                        return $priority;
                    }

                    return $left
                        ->id
                    <=>
                    $right
                        ->id;
                },
            )
            ->values()
            ->all();
    }

    private function organizationName(
        ?string $organizationName,
        string $siteName,
    ): string {
        $organizationName =
            $organizationName !== null
                ? trim(
                    $organizationName,
                )
                : '';

        if ($organizationName !== '') {
            return $organizationName;
        }

        return trim(
            $siteName,
        );
    }

    private function money(
        int $minorUnits,
    ): string {
        $negative =
            $minorUnits < 0;

        $absolute =
            abs(
                $minorUnits,
            );

        $major =
            intdiv(
                $absolute,
                100,
            );

        $minor =
            $absolute
            % 100;

        return (
            $negative
                ? '-'
                : ''
        )
        .$major
        .'.'
        .str_pad(
            (string) $minor,
            2,
            '0',
            STR_PAD_LEFT,
        );
    }

    private function baseUrl(
        ?string $canonicalHost,
    ): ?string {
        if (
            $canonicalHost === null
            || ! $this->isHttpUrl(
                $canonicalHost,
            )
        ) {
            return null;
        }

        return rtrim(
            $canonicalHost,
            '/',
        )
        .'/';
    }

    private function isHttpUrl(
        string $url,
    ): bool {
        $scheme =
            parse_url(
                $url,
                PHP_URL_SCHEME,
            );

        return is_string(
            $scheme,
        )
        && in_array(
            strtolower(
                $scheme,
            ),
            [
                'http',
                'https',
            ],
            true,
        );
    }
}
