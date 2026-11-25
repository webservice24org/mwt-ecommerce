<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

final readonly class MerchantPolicyReadinessData
{
    /**
     * @param  list<string>  $shippingOmissionReasons
     */
    public function __construct(
        public bool $structuredDataEnabled,
        public bool $shippingMarkupEnabled,
        public bool $shippingPublished,
        public array $shippingOmissionReasons,
        public bool $returnPolicyEnabled,
        public bool $returnPolicyPublished,
        public int $policyVersion,
    ) {}

    /**
     * @return array{
     *     structured_data_enabled: bool,
     *     shipping_markup_enabled: bool,
     *     shipping_published: bool,
     *     shipping_omission_reasons: list<string>,
     *     return_policy_enabled: bool,
     *     return_policy_published: bool,
     *     policy_version: int
     * }
     */
    public function toArray(): array
    {
        return [
            'structured_data_enabled' => $this->structuredDataEnabled,

            'shipping_markup_enabled' => $this->shippingMarkupEnabled,

            'shipping_published' => $this->shippingPublished,

            'shipping_omission_reasons' => $this->shippingOmissionReasons,

            'return_policy_enabled' => $this->returnPolicyEnabled,

            'return_policy_published' => $this->returnPolicyPublished,

            'policy_version' => $this->policyVersion,
        ];
    }
}
