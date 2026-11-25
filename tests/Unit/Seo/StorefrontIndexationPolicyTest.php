<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use App\Domain\Seo\Services\StorefrontIndexationPolicy;
use PHPUnit\Framework\TestCase;

final class StorefrontIndexationPolicyTest extends TestCase
{
    private StorefrontIndexationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy =
            new StorefrontIndexationPolicy;
    }

    public function test_base_listing_is_indexable(): void
    {
        $this->assertTrue(
            $this->policy
                ->allowsListingIndexing(
                    [],
                ),
        );
    }

    public function test_first_page_is_indexable(): void
    {
        $this->assertTrue(
            $this->policy
                ->allowsListingIndexing([
                    'page' => '1',
                ]),
        );
    }

    public function test_search_results_are_not_indexable(): void
    {
        $this->assertFalse(
            $this->policy
                ->allowsListingIndexing([
                    'q' => 'laptop',
                ]),
        );
    }

    public function test_sort_variation_is_not_indexable(): void
    {
        $this->assertFalse(
            $this->policy
                ->allowsListingIndexing([
                    'sort' => 'price_asc',
                ]),
        );
    }

    public function test_brand_filter_is_not_indexable(): void
    {
        $this->assertFalse(
            $this->policy
                ->allowsListingIndexing([
                    'brand' => 'apple',
                ]),
        );
    }

    public function test_category_filter_is_not_indexable(): void
    {
        $this->assertFalse(
            $this->policy
                ->allowsListingIndexing([
                    'category' => 'laptops',
                ]),
        );
    }

    public function test_price_filter_is_not_indexable(): void
    {
        $this->assertFalse(
            $this->policy
                ->allowsListingIndexing([
                    'min_price' => '10000',
                ]),
        );
    }

    public function test_attribute_filter_is_not_indexable(): void
    {
        $this->assertFalse(
            $this->policy
                ->allowsListingIndexing([
                    'attributes' => [
                        'color' => 'black',
                    ],
                ]),
        );
    }

    public function test_second_page_is_not_indexable(): void
    {
        $this->assertFalse(
            $this->policy
                ->allowsListingIndexing([
                    'page' => '2',
                ]),
        );
    }

    public function test_empty_filter_values_do_not_block_indexing(): void
    {
        $this->assertTrue(
            $this->policy
                ->allowsListingIndexing([
                    'q' => ' ',
                    'brand' => '',
                    'attributes' => [],
                ]),
        );
    }

    public function test_tracking_parameters_do_not_block_indexing(): void
    {
        $this->assertTrue(
            $this->policy
                ->allowsListingIndexing([
                    'utm_source' => 'facebook',

                    'utm_campaign' => 'summer',
                ]),
        );
    }

    public function test_invalid_page_variation_is_not_indexable(): void
    {
        $this->assertFalse(
            $this->policy
                ->allowsListingIndexing([
                    'page' => 'invalid',
                ]),
        );
    }

    public function test_zero_page_does_not_create_indexable_alternative(): void
    {
        $this->assertFalse(
            $this->policy
                ->allowsListingIndexing([
                    'page' => '0',
                ]),
        );
    }

    public function test_negative_page_is_not_indexable(): void
    {
        $this->assertFalse(
            $this->policy
                ->allowsListingIndexing([
                    'page' => '-1',
                ]),
        );
    }

    public function test_array_page_value_is_not_indexable(): void
    {
        $this->assertFalse(
            $this->policy
                ->allowsListingIndexing([
                    'page' => [
                        '2',
                    ],
                ]),
        );
    }

    public function test_nested_empty_attribute_filter_does_not_block_indexing(): void
    {
        $this->assertTrue(
            $this->policy
                ->allowsListingIndexing([
                    'attributes' => [
                        'color' => '',
                    ],
                ]),
        );
    }

    public function test_nested_meaningful_attribute_filter_blocks_indexing(): void
    {
        $this->assertFalse(
            $this->policy
                ->allowsListingIndexing([
                    'attributes' => [
                        'color' => 'black',
                    ],
                ]),
        );
    }
}
