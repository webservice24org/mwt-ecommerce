<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Seo;

use App\Domain\Seo\Merchant\Data\MerchantStructuredDataPolicyData;
use App\Domain\Seo\Merchant\Enums\MerchantRefundType;
use App\Domain\Seo\Merchant\Enums\MerchantReturnFees;
use App\Domain\Seo\Merchant\Enums\MerchantReturnMethod;
use App\Domain\Seo\Merchant\Enums\MerchantReturnPolicyCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMerchantStructuredDataPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'shipping_country_codes' => $this->countryCodes(
                $this->input(
                    'shipping_country_codes',
                    [],
                ),
            ),

            'return_applicable_country_codes' => $this->countryCodes(
                $this->input(
                    'return_applicable_country_codes',
                    [],
                ),
            ),

            'return_country_code' => $this->countryCode(
                $this->input(
                    'return_country_code',
                ),
            ),

            'return_policy_url' => $this->nullableString(
                $this->input(
                    'return_policy_url',
                ),
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shipping_markup_enabled' => [
                'required',
                'boolean',
            ],

            'shipping_country_codes' => [
                'array',
                'max:50',
            ],

            'shipping_country_codes.*' => [
                'string',
                'regex:/^[A-Z]{2}$/',
                'distinct',
            ],

            'return_policy_enabled' => [
                'required',
                'boolean',
            ],

            'return_applicable_country_codes' => [
                Rule::requiredIf(
                    $this->boolean(
                        'return_policy_enabled',
                    ),
                ),
                'array',
                'max:50',
            ],

            'return_applicable_country_codes.*' => [
                'string',
                'regex:/^[A-Z]{2}$/',
                'distinct',
            ],

            'return_policy_category' => [
                Rule::requiredIf(
                    $this->boolean(
                        'return_policy_enabled',
                    ),
                ),
                'nullable',
                Rule::enum(
                    MerchantReturnPolicyCategory::class,
                ),
            ],

            'return_days' => [
                Rule::requiredIf(
                    $this->input(
                        'return_policy_category',
                    ) ===
                    MerchantReturnPolicyCategory::Finite
                        ->value,
                ),
                'nullable',
                'integer',
                'min:1',
                'max:3650',
            ],

            'return_policy_url' => [
                'nullable',
                'url:http,https',
                'max:2048',
            ],

            'return_country_code' => [
                Rule::requiredIf(
                    $this->boolean(
                        'return_policy_enabled',
                    ),
                ),
                'nullable',
                'string',
                'regex:/^[A-Z]{2}$/',
            ],

            'return_method' => [
                'nullable',
                Rule::enum(
                    MerchantReturnMethod::class,
                ),
            ],

            'return_fees' => [
                'nullable',
                Rule::enum(
                    MerchantReturnFees::class,
                ),
            ],

            'return_shipping_fee_amount' => [
                Rule::requiredIf(
                    $this->input(
                        'return_fees',
                    ) ===
                    MerchantReturnFees::ShippingFee
                        ->value,
                ),
                'nullable',
                'integer',
                'min:1',
            ],

            'refund_type' => [
                'nullable',
                Rule::enum(
                    MerchantRefundType::class,
                ),
            ],

            'version' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    public function toData(): MerchantStructuredDataPolicyData
    {
        $returnPolicyEnabled =
            $this->boolean(
                'return_policy_enabled',
            );

        $returnPolicyCategory =
            $returnPolicyEnabled
                ? $this->validatedReturnPolicyCategory()
                : null;

        $returnsPermitted =
            $returnPolicyEnabled
            && $returnPolicyCategory !==
                MerchantReturnPolicyCategory::NotPermitted;

        $returnFees =
            $returnsPermitted
                ? $this->validatedReturnFees()
                : null;

        return new MerchantStructuredDataPolicyData(
            shippingMarkupEnabled: $this->boolean(
                'shipping_markup_enabled',
            ),

            shippingCountryCodes: $this->validatedCountryCodes(
                'shipping_country_codes',
            ),

            returnPolicyEnabled: $returnPolicyEnabled,

            returnApplicableCountryCodes: $returnPolicyEnabled
                    ? $this->validatedCountryCodes(
                        'return_applicable_country_codes',
                    )
                    : [],

            returnPolicyCategory: $returnPolicyCategory,

            returnDays: $returnPolicyCategory ===
                    MerchantReturnPolicyCategory::Finite
                    ? $this->validatedInteger(
                        'return_days',
                    )
                    : null,

            returnPolicyUrl: $returnPolicyEnabled
                    ? $this->validatedNullableString(
                        'return_policy_url',
                    )
                    : null,

            returnCountryCode: $returnPolicyEnabled
                    ? $this->validatedNullableString(
                        'return_country_code',
                    )
                    : null,

            returnMethod: $returnsPermitted
                    ? $this->validatedReturnMethod()
                    : null,

            returnFees: $returnFees,

            returnShippingFeeAmount: $returnFees ===
                    MerchantReturnFees::ShippingFee
                    ? $this->validatedInteger(
                        'return_shipping_fee_amount',
                    )
                    : null,

            refundType: $returnsPermitted
                    ? $this->validatedRefundType()
                    : null,

            version: $this->expectedVersion(),
        );
    }

    public function expectedVersion(): int
    {
        return $this->integer(
            'version',
        );
    }

    /**
     * @return list<string>
     */
    private function validatedCountryCodes(
        string $key,
    ): array {
        return $this->countryCodes(
            $this->validated(
                $key,
                [],
            ),
        );
    }

    private function validatedInteger(
        string $key,
    ): ?int {
        $value =
            $this->validated(
                $key,
            );

        if (is_int($value)) {
            return $value;
        }

        if (
            is_string($value)
            && preg_match(
                '/^-?\d+$/',
                $value,
            ) === 1
        ) {
            return (int) $value;
        }

        return null;
    }

    private function validatedNullableString(
        string $key,
    ): ?string {
        return $this->nullableString(
            $this->validated(
                $key,
            ),
        );
    }

    private function validatedReturnPolicyCategory(): ?MerchantReturnPolicyCategory
    {
        $value =
            $this->validated(
                'return_policy_category',
            );

        return is_string($value)
            ? MerchantReturnPolicyCategory::tryFrom(
                $value,
            )
            : null;
    }

    private function validatedReturnMethod(): ?MerchantReturnMethod
    {
        $value =
            $this->validated(
                'return_method',
            );

        return is_string($value)
            ? MerchantReturnMethod::tryFrom(
                $value,
            )
            : null;
    }

    private function validatedReturnFees(): ?MerchantReturnFees
    {
        $value =
            $this->validated(
                'return_fees',
            );

        return is_string($value)
            ? MerchantReturnFees::tryFrom(
                $value,
            )
            : null;
    }

    private function validatedRefundType(): ?MerchantRefundType
    {
        $value =
            $this->validated(
                'refund_type',
            );

        return is_string($value)
            ? MerchantRefundType::tryFrom(
                $value,
            )
            : null;
    }

    /**
     * @return list<string>
     */
    private function countryCodes(
        mixed $value,
    ): array {
        if (! is_array($value)) {
            return [];
        }

        $codes = [];

        foreach ($value as $country) {
            if (! is_string($country)) {
                continue;
            }

            $country =
                strtoupper(
                    trim(
                        $country,
                    ),
                );

            if (
                $country !== ''
                && ! in_array(
                    $country,
                    $codes,
                    true,
                )
            ) {
                $codes[] =
                    $country;
            }
        }

        return $codes;
    }

    private function countryCode(
        mixed $value,
    ): ?string {
        $value =
            $this->nullableString(
                $value,
            );

        return $value !== null
            ? strtoupper(
                $value,
            )
            : null;
    }

    private function nullableString(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return $value !== ''
            ? $value
            : null;
    }
}
