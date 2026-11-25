<?php

declare(strict_types=1);

namespace App\Domain\Seo\Merchant\Services;

use App\Domain\Seo\Merchant\Data\MerchantProductMetadataData;
use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;
use App\Domain\Seo\Merchant\Enums\MerchantProductCondition;
use App\Models\MerchantProductData;
use App\Models\MerchantProductDataEvent;
use App\Models\Product;
use App\Models\ProductVariant;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class MerchantProductDataService
{
    public function __construct(
        private MerchantGtinValidator $gtin,
    ) {}

    public function get(
        Product $product,
        ?ProductVariant $variant = null,
    ): MerchantProductMetadataData {
        $this->assertVariantBelongsToProduct(
            product: $product,

            variant: $variant,
        );

        $model =
            MerchantProductData::query()
                ->where(
                    'item_key',
                    $this->itemKey(
                        product: $product,

                        variant: $variant,
                    ),
                )
                ->first();

        return $model !== null
            ? $this->toData(
                $model,
            )
            : $this->defaults();
    }

    public function save(
        Product $product,
        ?ProductVariant $variant,
        MerchantProductMetadataData $data,
        int $expectedVersion,
        ?int $changedByAdminId = null,
    ): MerchantProductMetadataData {
        $this->assertVariantBelongsToProduct(
            product: $product,

            variant: $variant,
        );

        $normalized =
            $this->normalize(
                $data,
            );

        $saved =
            DB::transaction(
                function () use (
                    $product,
                    $variant,
                    $normalized,
                    $expectedVersion,
                    $changedByAdminId,
                ): MerchantProductData {
                    $itemKey =
                        $this->itemKey(
                            product: $product,

                            variant: $variant,
                        );

                    $existing =
                        MerchantProductData::query()
                            ->where(
                                'item_key',
                                $itemKey,
                            )
                            ->lockForUpdate()
                            ->first();

                    if ($existing === null) {
                        if (
                            $expectedVersion !==
                            0
                        ) {
                            throw new DomainException(
                                'Merchant product data no longer matches the version you loaded.',
                            );
                        }

                        $created =
                            MerchantProductData::query()
                                ->create([
                                    'item_key' => $itemKey,

                                    'product_id' => $product->id,

                                    'product_variant_id' => $variant
                                        ?->id,

                                    ...$this->values(
                                        $normalized,
                                    ),

                                    'version' => 1,

                                    'updated_by_admin_id' => $changedByAdminId,
                                ]);

                        MerchantProductDataEvent::query()
                            ->create([
                                'merchant_product_data_id' => $created->id,

                                'version' => 1,

                                'before_data' => null,

                                'after_data' => $this->values(
                                    $normalized,
                                ),

                                'changed_by_admin_id' => $changedByAdminId,

                                'created_at' => now(),
                            ]);

                        return $created;
                    }

                    if (
                        $existing->version !==
                        $expectedVersion
                    ) {
                        throw new DomainException(
                            'Merchant product data changed since it was loaded. Refresh before saving.',
                        );
                    }

                    $before =
                        $this->values(
                            $this->toData(
                                $existing,
                            ),
                        );

                    $after =
                        $this->values(
                            $normalized,
                        );

                    if ($before === $after) {
                        return $existing;
                    }

                    $nextVersion =
                        $existing->version
                        + 1;

                    $existing->update([
                        ...$after,

                        'version' => $nextVersion,

                        'updated_by_admin_id' => $changedByAdminId,
                    ]);

                    MerchantProductDataEvent::query()
                        ->create([
                            'merchant_product_data_id' => $existing->id,

                            'version' => $nextVersion,

                            'before_data' => $before,

                            'after_data' => $after,

                            'changed_by_admin_id' => $changedByAdminId,

                            'created_at' => now(),
                        ]);

                    return $existing
                        ->refresh();
                },
            );

        return $this->toData(
            $saved,
        );
    }

    public function defaults(): MerchantProductMetadataData
    {
        return new MerchantProductMetadataData(
            condition: MerchantProductCondition::New,

            identifierState: MerchantIdentifierState::Unknown,

            gtin: null,

            mpn: null,

            googleProductCategory: null,

            version: 0,
        );
    }

    /**
     * @param  list<int>  $productIds
     * @return array<string, MerchantProductMetadataData>
     */
    public function mapForProducts(
        array $productIds,
    ): array {
        if ($productIds === []) {
            return [];
        }

        $map = [];

        $records =
            MerchantProductData::query()
                ->whereIn(
                    'product_id',
                    $productIds,
                )
                ->get();

        foreach ($records as $record) {
            $map[
                $record
                    ->item_key
            ] =
                $this->toData(
                    $record,
                );
        }

        return $map;
    }

    public function itemKey(
        Product $product,
        ?ProductVariant $variant = null,
    ): string {
        return $variant !== null
            ? 'variant:'
                .$variant->id
            : 'product:'
                .$product->id;
    }

    private function normalize(
        MerchantProductMetadataData $data,
    ): MerchantProductMetadataData {
        $gtin =
            $this->nullableText(
                $data->gtin,
            );

        if ($gtin !== null) {
            $normalizedGtin =
                preg_replace(
                    '/\s+/',
                    '',
                    $gtin,
                );

            $gtin =
                is_string(
                    $normalizedGtin,
                )
                    ? $normalizedGtin
                    : $gtin;
        }

        if (
            $gtin !== null
            && ! $this
                ->gtin
                ->valid(
                    $gtin,
                )
        ) {
            throw new DomainException(
                'GTIN must be a valid 8, 12, 13, or 14 digit identifier with a valid check digit.',
            );
        }

        $mpn =
            $this->nullableText(
                $data->mpn,
            );

        if (
            $mpn !== null
            && mb_strlen(
                $mpn,
            ) > 70
        ) {
            throw new DomainException(
                'Merchant MPN cannot exceed 70 characters.',
            );
        }

        $category =
            $this->nullableText(
                $data->googleProductCategory,
            );

        if (
            $category !== null
            && mb_strlen(
                $category,
            ) > 255
        ) {
            throw new DomainException(
                'Google product category cannot exceed 255 characters.',
            );
        }

        return new MerchantProductMetadataData(
            condition: $data->condition,

            identifierState: $data->identifierState,

            gtin: $gtin,

            mpn: $mpn,

            googleProductCategory: $category,

            version: $data->version,
        );
    }

    /**
     * @return array{
     *     condition: string,
     *     identifier_state: string,
     *     gtin: string|null,
     *     mpn: string|null,
     *     google_product_category: string|null
     * }
     */
    private function values(
        MerchantProductMetadataData $data,
    ): array {
        return [
            'condition' => $data
                ->condition
                ->value,

            'identifier_state' => $data
                ->identifierState
                ->value,

            'gtin' => $data->gtin,

            'mpn' => $data->mpn,

            'google_product_category' => $data
                ->googleProductCategory,
        ];
    }

    private function toData(
        MerchantProductData $model,
    ): MerchantProductMetadataData {
        return new MerchantProductMetadataData(
            condition: $model
                ->condition,

            identifierState: $model
                ->identifier_state,

            gtin: $model->gtin,

            mpn: $model->mpn,

            googleProductCategory: $model
                ->google_product_category,

            version: $model
                ->version,
        );
    }

    private function assertVariantBelongsToProduct(
        Product $product,
        ?ProductVariant $variant,
    ): void {
        if (
            $variant !== null
            && $variant
                ->product_id !==
                $product
                    ->id
        ) {
            throw new DomainException(
                'Merchant variant metadata must belong to the selected product.',
            );
        }
    }

    private function nullableText(
        ?string $value,
    ): ?string {
        if ($value === null) {
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
