<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Merchant;

use App\Domain\Seo\Merchant\Data\MerchantProductMetadataData;
use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;
use App\Domain\Seo\Merchant\Enums\MerchantProductCondition;
use App\Domain\Seo\Merchant\Services\MerchantProductDataService;
use App\Models\Product;
use App\Models\ProductVariant;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MerchantProductDataServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_metadata_returns_safe_defaults_without_creating_a_row(): void
    {
        $product =
            Product::factory()
                ->create();

        $data =
            app(
                MerchantProductDataService::class,
            )->get(
                $product,
            );

        self::assertSame(
            MerchantProductCondition::New,
            $data->condition,
        );

        self::assertSame(
            MerchantIdentifierState::Unknown,
            $data->identifierState,
        );

        self::assertSame(
            0,
            $data->version,
        );

        self::assertDatabaseCount(
            'merchant_product_data',
            0,
        );
    }

    public function test_product_metadata_is_created_versioned_and_audited(): void
    {
        $product =
            Product::factory()
                ->create();

        $service =
            app(
                MerchantProductDataService::class,
            );

        $created =
            $service->save(
                product: $product,

                variant: null,

                data: new MerchantProductMetadataData(
                    condition: MerchantProductCondition::New,

                    identifierState: MerchantIdentifierState::Available,

                    gtin: '4006381333931',

                    mpn: 'MWT-CABLE-001',

                    googleProductCategory: 'Electronics > Cables',

                    version: 0,
                ),

                expectedVersion: 0,

                changedByAdminId: 77,
            );

        self::assertSame(
            1,
            $created->version,
        );

        self::assertDatabaseHas(
            'merchant_product_data',
            [
                'item_key' => 'product:'
                    .$product->id,

                'product_id' => $product->id,

                'product_variant_id' => null,

                'gtin' => '4006381333931',

                'version' => 1,
            ],
        );

        self::assertDatabaseHas(
            'merchant_product_data_events',
            [
                'version' => 1,

                'changed_by_admin_id' => 77,
            ],
        );

        $updated =
            $service->save(
                product: $product,

                variant: null,

                data: new MerchantProductMetadataData(
                    condition: MerchantProductCondition::New,

                    identifierState: MerchantIdentifierState::Available,

                    gtin: '4006381333931',

                    mpn: 'MWT-CABLE-002',

                    googleProductCategory: 'Electronics > Cables',

                    version: 1,
                ),

                expectedVersion: 1,
            );

        self::assertSame(
            2,
            $updated->version,
        );

        self::assertDatabaseCount(
            'merchant_product_data_events',
            2,
        );
    }

    public function test_variant_metadata_requires_variant_to_belong_to_product(): void
    {
        $product =
            Product::factory()
                ->create();

        $other =
            Product::factory()
                ->create();

        $variant =
            ProductVariant::factory()
                ->create([
                    'product_id' => $other->id,
                ]);

        $this->expectException(
            DomainException::class,
        );

        app(
            MerchantProductDataService::class,
        )->get(
            product: $product,

            variant: $variant,
        );
    }

    public function test_invalid_gtin_is_rejected_before_persistence(): void
    {
        $product =
            Product::factory()
                ->create();

        $this->expectException(
            DomainException::class,
        );

        try {
            app(
                MerchantProductDataService::class,
            )->save(
                product: $product,

                variant: null,

                data: new MerchantProductMetadataData(
                    condition: MerchantProductCondition::New,

                    identifierState: MerchantIdentifierState::Available,

                    gtin: '4006381333932',

                    mpn: null,

                    googleProductCategory: null,

                    version: 0,
                ),

                expectedVersion: 0,
            );
        } finally {
            self::assertDatabaseCount(
                'merchant_product_data',
                0,
            );
        }
    }

    public function test_stale_version_is_rejected(): void
    {
        $product =
            Product::factory()
                ->create();

        $service =
            app(
                MerchantProductDataService::class,
            );

        $data =
            new MerchantProductMetadataData(
                condition: MerchantProductCondition::New,

                identifierState: MerchantIdentifierState::Unknown,

                gtin: null,

                mpn: null,

                googleProductCategory: null,

                version: 0,
            );

        $service->save(
            product: $product,

            variant: null,

            data: $data,

            expectedVersion: 0,
        );

        $this->expectException(
            DomainException::class,
        );

        $service->save(
            product: $product,

            variant: null,

            data: $data,

            expectedVersion: 0,
        );
    }
}
