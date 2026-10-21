<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Domain\Orders\Enums\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CheckoutPaymentSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_defaults_to_cash_on_delivery(): void
    {
        $this->startSession();

        $service = app(
            CheckoutSessionService::class,
        );

        $method =
            $service->paymentMethod();

        $this->assertSame(
            PaymentMethod::CashOnDelivery,
            $method,
        );

        $this->assertSame(
            'cash_on_delivery',
            session(
                'storefront.checkout.payment_method',
            ),
        );
    }

    public function test_invalid_payment_session_value_is_replaced_with_cash_on_delivery(): void
    {
        $this->startSession();

        session()->put(
            'storefront.checkout.payment_method',
            'unsupported_gateway',
        );

        $service = app(
            CheckoutSessionService::class,
        );

        $method =
            $service->paymentMethod();

        $this->assertSame(
            PaymentMethod::CashOnDelivery,
            $method,
        );

        $this->assertSame(
            'cash_on_delivery',
            session(
                'storefront.checkout.payment_method',
            ),
        );
    }

    public function test_supported_payment_method_can_be_stored(): void
    {
        $this->startSession();

        $service = app(
            CheckoutSessionService::class,
        );

        $service->selectPaymentMethod(
            PaymentMethod::CashOnDelivery,
        );

        $this->assertSame(
            'cash_on_delivery',
            session(
                'storefront.checkout.payment_method',
            ),
        );
    }
}
