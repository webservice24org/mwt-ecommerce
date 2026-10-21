<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Domain\Orders\Actions\CreateOrderAction;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DuplicateSubmissionStockRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_submission_token_returns_same_order(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $product = Product::factory()
            ->create([
                'name' => 'Duplicate Submission Product',

                'sku' => 'DUPLICATE-001',

                'price' => 25000,

                'status' => 'published',
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        $token = app(
            CheckoutSessionService::class,
        )->orderSubmissionToken();

        $action = app(
            CreateOrderAction::class,
        );

        $firstOrder = $action
            ->execute(
                $user,
                $token,
            );

        $secondOrder = $action
            ->execute(
                $user,
                $token,
            );

        $this->assertSame(
            $firstOrder->id,
            $secondOrder->id,
        );

        $this->assertDatabaseCount(
            'orders',
            1,
        );

        $this->assertDatabaseCount(
            'order_items',
            1,
        );

        $this->assertSame(
            $token,
            $firstOrder->idempotency_key,
        );
    }
}
