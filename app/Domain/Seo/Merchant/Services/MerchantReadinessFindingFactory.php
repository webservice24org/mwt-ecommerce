<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Services;

use App\Domain\Seo\Merchant\Data\MerchantListingFindingData;
use App\Domain\Seo\Merchant\Enums\MerchantListingFindingSeverity;

final readonly class MerchantReadinessFindingFactory
{
    public function exclusion(
        string $id,
    ): MerchantListingFindingData {
        return match ($id) {
            'variable_product_has_no_active_variants' => $this->finding(
                id: $id,
                severity: MerchantListingFindingSeverity::Blocker,
                category: 'variants',
                message: 'The variable product has no active merchant-exportable variants.',
                action: 'Publish at least one real active variant with authoritative price and availability.',
            ),

            'invalid_gtin' => $this->finding(
                id: $id,
                severity: MerchantListingFindingSeverity::Blocker,
                category: 'identifiers',
                message: 'The explicitly stored merchant GTIN is invalid.',
                action: 'Correct the GTIN using a valid 8, 12, 13, or 14 digit identifier with a valid check digit.',
            ),

            'non_new_condition_not_visible_on_landing_page' => $this->finding(
                id: $id,
                severity: MerchantListingFindingSeverity::Blocker,
                category: 'landing_page',
                message: 'Merchant condition is non-new but that condition is not visibly represented on the landing page.',
                action: 'Do not export used/refurbished condition until the storefront truthfully exposes the same condition.',
            ),

            'item_price_missing' => $this->finding(
                id: $id,
                severity: MerchantListingFindingSeverity::Blocker,
                category: 'offer',
                message: 'The feed item does not have a positive authoritative selling price.',
                action: 'Correct the catalog price source before merchant export.',
            ),

            'item_image_missing' => $this->finding(
                id: $id,
                severity: MerchantListingFindingSeverity::Blocker,
                category: 'image',
                message: 'The feed item does not have a merchant-ready image URL.',
                action: 'Add a valid product image before merchant export.',
            ),

            'item_link_missing_or_unsafe' => $this->finding(
                id: $id,
                severity: MerchantListingFindingSeverity::Blocker,
                category: 'landing_page',
                message: 'The feed item does not have a safe HTTP(S) landing URL.',
                action: 'Correct the authoritative canonical URL before merchant export.',
            ),

            'variant_not_active_or_not_owned',
            'simple_product_cannot_export_variant',
            'variable_product_requires_variant_item' => $this->finding(
                id: $id,
                severity: MerchantListingFindingSeverity::Blocker,
                category: 'variants',
                message: 'The product/variant combination cannot be emitted by the merchant feed contract.',
                action: 'Correct the product/variant state; do not bypass the M.3 feed item contract.',
            ),

            default => $this->finding(
                id: $id,
                severity: MerchantListingFindingSeverity::Blocker,
                category: 'feed',
                message: 'The M.3 feed builder excluded this item.',
                action: 'Resolve the reported feed exclusion before export.',
            ),
        };
    }

    public function warning(
        string $id,
    ): MerchantListingFindingData {
        if (
            $id ===
            'identifier_declared_available_but_values_missing'
        ) {
            return $this->finding(
                id: $id,
                severity: MerchantListingFindingSeverity::Warning,
                category: 'identifiers',
                message: 'Identifiers were declared available, but neither GTIN nor MPN is stored for this merchant item.',
                action: 'Store the real manufacturer identifier values or correct the identifier declaration.',
            );
        }

        return $this->finding(
            id: $id,
            severity: MerchantListingFindingSeverity::Warning,
            category: 'feed',
            message: 'The M.3 feed builder reported a merchant-data warning.',
            action: 'Review the reported warning before export.',
        );
    }

    private function finding(
        string $id,
        MerchantListingFindingSeverity $severity,
        string $category,
        string $message,
        string $action,
    ): MerchantListingFindingData {
        return new MerchantListingFindingData(
            id: $id,
            severity: $severity,
            category: $category,
            message: $message,
            action: $action,
        );
    }
}
