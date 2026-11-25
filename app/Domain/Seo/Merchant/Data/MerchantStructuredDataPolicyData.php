<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

use App\Domain\Seo\Merchant\Enums\MerchantRefundType;
use App\Domain\Seo\Merchant\Enums\MerchantReturnFees;
use App\Domain\Seo\Merchant\Enums\MerchantReturnMethod;
use App\Domain\Seo\Merchant\Enums\MerchantReturnPolicyCategory;

final readonly class MerchantStructuredDataPolicyData
{
    /**
     * @param  list<string>  $shippingCountryCodes
     * @param  list<string>  $returnApplicableCountryCodes
     */
    public function __construct(
        public bool $shippingMarkupEnabled,
        public array $shippingCountryCodes,
        public bool $returnPolicyEnabled,
        public array $returnApplicableCountryCodes,
        public ?MerchantReturnPolicyCategory $returnPolicyCategory,
        public ?int $returnDays,
        public ?string $returnPolicyUrl,
        public ?string $returnCountryCode,
        public ?MerchantReturnMethod $returnMethod,
        public ?MerchantReturnFees $returnFees,
        public ?int $returnShippingFeeAmount,
        public ?MerchantRefundType $refundType,
        public int $version,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'shipping_markup_enabled' => $this->shippingMarkupEnabled,

            'shipping_country_codes' => $this->shippingCountryCodes,

            'return_policy_enabled' => $this->returnPolicyEnabled,

            'return_applicable_country_codes' => $this->returnApplicableCountryCodes,

            'return_policy_category' => $this->returnPolicyCategory
                ?->value,

            'return_days' => $this->returnDays,

            'return_policy_url' => $this->returnPolicyUrl,

            'return_country_code' => $this->returnCountryCode,

            'return_method' => $this->returnMethod
                ?->value,

            'return_fees' => $this->returnFees
                ?->value,

            'return_shipping_fee_amount' => $this->returnShippingFeeAmount,

            'refund_type' => $this->refundType
                ?->value,

            'version' => $this->version,
        ];
    }
}
