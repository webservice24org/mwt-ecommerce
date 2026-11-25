<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Services;

use App\Domain\Seo\Merchant\Data\MerchantStructuredDataPolicyData;
use App\Models\MerchantStructuredDataPolicy;
use App\Models\MerchantStructuredDataPolicyEvent;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class MerchantStructuredDataPolicyService
{
    public function get(): MerchantStructuredDataPolicyData
    {
        return $this->toData(
            $this->policy(),
        );
    }

    public function save(
        MerchantStructuredDataPolicyData $data,
        int $expectedVersion,
        ?int $changedByAdminId = null,
    ): MerchantStructuredDataPolicyData {
        $saved =
            DB::transaction(
                function () use (
                    $data,
                    $expectedVersion,
                    $changedByAdminId,
                ): MerchantStructuredDataPolicy {
                    $policy =
                        MerchantStructuredDataPolicy::query()
                            ->where(
                                'scope_key',
                                'global',
                            )
                            ->lockForUpdate()
                            ->first();

                    if ($policy === null) {
                        $policy =
                            $this->createDefault();

                        $policy =
                            MerchantStructuredDataPolicy::query()
                                ->lockForUpdate()
                                ->findOrFail(
                                    $policy->id,
                                );
                    }

                    if (
                        $policy->version !==
                        $expectedVersion
                    ) {
                        throw new DomainException(
                            'Merchant structured-data policy changed since this page was loaded. Refresh before saving.',
                        );
                    }

                    $before =
                        $this->policyValues(
                            $policy,
                        );

                    $after = [
                        'shipping_markup_enabled' => $data->shippingMarkupEnabled,

                        'shipping_country_codes' => $this->countryCodes(
                            $data->shippingCountryCodes,
                        ),

                        'return_policy_enabled' => $data->returnPolicyEnabled,

                        'return_applicable_country_codes' => $this->countryCodes(
                            $data->returnApplicableCountryCodes,
                        ),

                        'return_policy_category' => $data->returnPolicyCategory
                            ?->value,

                        'return_days' => $data->returnDays,

                        'return_policy_url' => $data->returnPolicyUrl,

                        'return_country_code' => $data->returnCountryCode !== null
                                ? strtoupper(
                                    $data->returnCountryCode,
                                )
                                : null,

                        'return_method' => $data->returnMethod
                            ?->value,

                        'return_fees' => $data->returnFees
                            ?->value,

                        'return_shipping_fee_amount' => $data->returnShippingFeeAmount,

                        'refund_type' => $data->refundType
                            ?->value,
                    ];

                    if ($before === $after) {
                        return $policy;
                    }

                    $nextVersion =
                        $policy->version
                        + 1;

                    $policy->update([
                        ...$after,

                        'version' => $nextVersion,

                        'updated_by_admin_id' => $changedByAdminId,
                    ]);

                    MerchantStructuredDataPolicyEvent::query()
                        ->create([
                            'merchant_structured_data_policy_id' => $policy->id,

                            'version' => $nextVersion,

                            'before_policy' => $before,

                            'after_policy' => $after,

                            'changed_by_admin_id' => $changedByAdminId,

                            'created_at' => now(),
                        ]);

                    return $policy
                        ->refresh();
                },
            );

        return $this->toData(
            $saved,
        );
    }

    private function policy(): MerchantStructuredDataPolicy
    {
        return MerchantStructuredDataPolicy::query()
            ->firstOrCreate(
                [
                    'scope_key' => 'global',
                ],
                [
                    'shipping_markup_enabled' => false,

                    'shipping_country_codes' => [],

                    'return_policy_enabled' => false,

                    'return_applicable_country_codes' => [],

                    'version' => 1,
                ],
            );
    }

    private function createDefault(): MerchantStructuredDataPolicy
    {
        return MerchantStructuredDataPolicy::query()
            ->create([
                'scope_key' => 'global',

                'shipping_markup_enabled' => false,

                'shipping_country_codes' => [],

                'return_policy_enabled' => false,

                'return_applicable_country_codes' => [],

                'version' => 1,
            ]);
    }

    private function toData(
        MerchantStructuredDataPolicy $policy,
    ): MerchantStructuredDataPolicyData {
        return new MerchantStructuredDataPolicyData(
            shippingMarkupEnabled: $policy
                ->shipping_markup_enabled,

            shippingCountryCodes: $this->countryCodes(
                $policy
                    ->shipping_country_codes
                ?? [],
            ),

            returnPolicyEnabled: $policy
                ->return_policy_enabled,

            returnApplicableCountryCodes: $this->countryCodes(
                $policy
                    ->return_applicable_country_codes
                ?? [],
            ),

            returnPolicyCategory: $policy
                ->return_policy_category,

            returnDays: $policy
                ->return_days,

            returnPolicyUrl: $policy
                ->return_policy_url,

            returnCountryCode: $policy
                ->return_country_code,

            returnMethod: $policy
                ->return_method,

            returnFees: $policy
                ->return_fees,

            returnShippingFeeAmount: $policy
                ->return_shipping_fee_amount,

            refundType: $policy
                ->refund_type,

            version: $policy
                ->version,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function policyValues(
        MerchantStructuredDataPolicy $policy,
    ): array {
        return [
            'shipping_markup_enabled' => $policy
                ->shipping_markup_enabled,

            'shipping_country_codes' => $this->countryCodes(
                $policy
                    ->shipping_country_codes
                ?? [],
            ),

            'return_policy_enabled' => $policy
                ->return_policy_enabled,

            'return_applicable_country_codes' => $this->countryCodes(
                $policy
                    ->return_applicable_country_codes
                ?? [],
            ),

            'return_policy_category' => $policy
                ->return_policy_category
                ?->value,

            'return_days' => $policy
                ->return_days,

            'return_policy_url' => $policy
                ->return_policy_url,

            'return_country_code' => $policy
                ->return_country_code,

            'return_method' => $policy
                ->return_method
                ?->value,

            'return_fees' => $policy
                ->return_fees
                ?->value,

            'return_shipping_fee_amount' => $policy
                ->return_shipping_fee_amount,

            'refund_type' => $policy
                ->refund_type
                ?->value,
        ];
    }

    /**
     * @param  array<int, mixed>  $codes
     * @return list<string>
     */
    private function countryCodes(
        array $codes,
    ): array {
        $normalized = [];

        foreach ($codes as $code) {
            if (! is_string($code)) {
                continue;
            }

            $code =
                strtoupper(
                    trim(
                        $code,
                    ),
                );

            if (
                preg_match(
                    '/^[A-Z]{2}$/',
                    $code,
                ) !== 1
                || in_array(
                    $code,
                    $normalized,
                    true,
                )
            ) {
                continue;
            }

            $normalized[] =
                $code;
        }

        sort(
            $normalized,
            SORT_STRING,
        );

        return $normalized;
    }
}
