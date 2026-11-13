<?php

declare(strict_types=1);

namespace Tests\Feature\Reviews;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Reviews\Services\ProductReviewEligibilityService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductReviewEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_delivered_purchase_is_eligible(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->qualifyingOrder(
                customer: $customer,
            );

        $this->attachProduct(
            order: $order,

            product: $product,
        );

        $result =
            app(
                ProductReviewEligibilityService::class,
            )->check(
                user: $customer,

                product: $product,
            );

        $this->assertTrue(
            $result->eligible,
        );

        $this->assertTrue(
            $result
                ->verifiedPurchase,
        );

        $this->assertFalse(
            $result
                ->alreadyReviewed,
        );

        $this->assertSame(
            $order->id,
            $result
                ->verifiedOrderId,
        );

        $this->assertNull(
            $result->reason,
        );
    }

    public function test_customer_without_purchase_is_not_eligible(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $result =
            app(
                ProductReviewEligibilityService::class,
            )->check(
                user: $customer,

                product: $product,
            );

        $this->assertFalse(
            $result->eligible,
        );

        $this->assertFalse(
            $result
                ->verifiedPurchase,
        );

        $this->assertNull(
            $result
                ->verifiedOrderId,
        );

        $this->assertSame(
            'You can review this product after a completed delivery.',
            $result->reason,
        );
    }

    public function test_another_customers_order_does_not_qualify(): void
    {
        $customer =
            User::factory()
                ->create();

        $otherCustomer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->qualifyingOrder(
                customer: $otherCustomer,
            );

        $this->attachProduct(
            order: $order,

            product: $product,
        );

        $result =
            app(
                ProductReviewEligibilityService::class,
            )->check(
                user: $customer,

                product: $product,
            );

        $this->assertFalse(
            $result->eligible,
        );

        $this->assertNull(
            $result
                ->verifiedOrderId,
        );
    }

    public function test_different_product_purchase_does_not_qualify(): void
    {
        $customer =
            User::factory()
                ->create();

        $purchasedProduct =
            Product::factory()
                ->create();

        $requestedProduct =
            Product::factory()
                ->create();

        $order =
            $this->qualifyingOrder(
                customer: $customer,
            );

        $this->attachProduct(
            order: $order,

            product: $purchasedProduct,
        );

        $result =
            app(
                ProductReviewEligibilityService::class,
            )->check(
                user: $customer,

                product: $requestedProduct,
            );

        $this->assertFalse(
            $result->eligible,
        );
    }

    public function test_pending_order_does_not_qualify(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'status' => OrderStatus::Pending,

                    'fulfilment_status' => FulfilmentStatus::Unfulfilled,
                ]);

        $this->attachProduct(
            order: $order,

            product: $product,
        );

        $result =
            app(
                ProductReviewEligibilityService::class,
            )->check(
                user: $customer,

                product: $product,
            );

        $this->assertFalse(
            $result->eligible,
        );
    }

    public function test_processing_order_does_not_qualify(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'status' => OrderStatus::Processing,

                    'fulfilment_status' => FulfilmentStatus::Processing,
                ]);

        $this->attachProduct(
            order: $order,

            product: $product,
        );

        $result =
            app(
                ProductReviewEligibilityService::class,
            )->check(
                user: $customer,

                product: $product,
            );

        $this->assertFalse(
            $result->eligible,
        );
    }

    public function test_shipped_order_does_not_qualify(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'status' => OrderStatus::Processing,

                    'fulfilment_status' => FulfilmentStatus::Shipped,
                ]);

        $this->attachProduct(
            order: $order,

            product: $product,
        );

        $result =
            app(
                ProductReviewEligibilityService::class,
            )->check(
                user: $customer,

                product: $product,
            );

        $this->assertFalse(
            $result->eligible,
        );
    }

    public function test_delivered_but_not_completed_order_does_not_qualify(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'status' => OrderStatus::Processing,

                    'fulfilment_status' => FulfilmentStatus::Delivered,
                ]);

        $this->attachProduct(
            order: $order,

            product: $product,
        );

        $result =
            app(
                ProductReviewEligibilityService::class,
            )->check(
                user: $customer,

                product: $product,
            );

        $this->assertFalse(
            $result->eligible,
        );
    }

    public function test_cancelled_order_does_not_qualify(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'status' => OrderStatus::Cancelled,

                    'fulfilment_status' => FulfilmentStatus::Unfulfilled,
                ]);

        $this->attachProduct(
            order: $order,

            product: $product,
        );

        $result =
            app(
                ProductReviewEligibilityService::class,
            )->check(
                user: $customer,

                product: $product,
            );

        $this->assertFalse(
            $result->eligible,
        );
    }

    public function test_returned_order_does_not_qualify(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            Order::factory()
                ->for(
                    $customer,
                )
                ->create([
                    'status' => OrderStatus::Completed,

                    'fulfilment_status' => FulfilmentStatus::Returned,
                ]);

        $this->attachProduct(
            order: $order,

            product: $product,
        );

        $result =
            app(
                ProductReviewEligibilityService::class,
            )->check(
                user: $customer,

                product: $product,
            );

        $this->assertFalse(
            $result->eligible,
        );
    }

    public function test_existing_review_makes_customer_ineligible(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $order =
            $this->qualifyingOrder(
                customer: $customer,
            );

        $this->attachProduct(
            order: $order,

            product: $product,
        );

        ProductReview::factory()
            ->create([
                'product_id' => $product->id,

                'user_id' => $customer->id,

                'verified_order_id' => $order->id,

                'is_verified_purchase' => true,
            ]);

        $result =
            app(
                ProductReviewEligibilityService::class,
            )->check(
                user: $customer,

                product: $product,
            );

        $this->assertFalse(
            $result->eligible,
        );

        $this->assertTrue(
            $result
                ->alreadyReviewed,
        );

        $this->assertSame(
            'You have already reviewed this product.',
            $result->reason,
        );
    }

    public function test_latest_qualifying_order_is_used_for_verification(): void
    {
        $customer =
            User::factory()
                ->create();

        $product =
            Product::factory()
                ->create();

        $older =
            $this->qualifyingOrder(
                customer: $customer,

                placedAt: '2026-10-01 12:00:00',
            );

        $newer =
            $this->qualifyingOrder(
                customer: $customer,

                placedAt: '2026-11-01 12:00:00',
            );

        $this->attachProduct(
            order: $older,

            product: $product,
        );

        $this->attachProduct(
            order: $newer,

            product: $product,
        );

        $result =
            app(
                ProductReviewEligibilityService::class,
            )->check(
                user: $customer,

                product: $product,
            );

        $this->assertTrue(
            $result->eligible,
        );

        $this->assertSame(
            $newer->id,
            $result
                ->verifiedOrderId,
        );
    }

    private function qualifyingOrder(
        User $customer,
        string $placedAt = '2026-11-10 12:00:00',
    ): Order {
        return Order::factory()
            ->for(
                $customer,
            )
            ->create([
                'status' => OrderStatus::Completed,

                'fulfilment_status' => FulfilmentStatus::Delivered,

                'placed_at' => $placedAt,
            ]);
    }

    private function attachProduct(
        Order $order,
        Product $product,
    ): OrderItem {
        return OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'product_id' => $product->id,

                'variant_id' => null,

                'product_name' => $product->name,

                'sku' => $product->sku,

                'unit_price' => 10000,

                'quantity' => 1,

                'line_total' => 10000,
            ]);
    }
}
