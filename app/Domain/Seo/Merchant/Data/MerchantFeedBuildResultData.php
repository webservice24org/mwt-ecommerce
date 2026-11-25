<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Data;

final readonly class MerchantFeedBuildResultData
{
    /**
     * @param  list<string>  $exclusionReasons
     * @param  list<string>  $warnings
     */
    public function __construct(
        public ?MerchantFeedItemData $item,
        public array $exclusionReasons,
        public array $warnings,
        public ?MerchantListingReadinessResultData $readiness = null,
    ) {}

    public function included(): bool
    {
        return $this->item !==
            null;
    }
}
