<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Services;

use App\Domain\Orders\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;

final class CheckoutSessionService
{
    private const ADDRESS_ID_KEY =
        'storefront.checkout.address_id';

    private const PAYMENT_METHOD_KEY =
        'storefront.checkout.payment_method';

    private const ORDER_SUBMISSION_TOKEN_KEY =
        'storefront.checkout.order_submission_token';

    public function __construct(
        private readonly Session $session,
    ) {}

    public function selectedAddressId(): ?int
    {
        $value = $this->session->get(
            self::ADDRESS_ID_KEY,
        );

        if (! is_int($value)) {
            return null;
        }

        return $value;
    }

    public function selectAddress(
        Address $address,
    ): void {
        $this->session->put(
            self::ADDRESS_ID_KEY,
            $address->id,
        );
    }

    public function forgetAddress(): void
    {
        $this->session->forget(
            self::ADDRESS_ID_KEY,
        );
    }

    public function resolveAddress(
        User $user,
    ): ?Address {
        $selectedId =
            $this->selectedAddressId();

        if ($selectedId !== null) {
            $selected = $user
                ->addresses()
                ->whereKey(
                    $selectedId,
                )
                ->first();

            if ($selected !== null) {
                return $selected;
            }

            $this->forgetAddress();
        }

        $address = $user
            ->addresses()
            ->orderByDesc(
                'is_default',
            )
            ->orderBy('id')
            ->first();

        if ($address !== null) {
            $this->selectAddress(
                $address,
            );
        }

        return $address;
    }

    public function paymentMethod(): PaymentMethod
    {
        $value = $this->session->get(
            self::PAYMENT_METHOD_KEY,
        );

        if (is_string($value)) {
            $method = PaymentMethod::tryFrom(
                $value,
            );

            if ($method !== null) {
                return $method;
            }
        }

        $method =
            PaymentMethod::CashOnDelivery;

        $this->selectPaymentMethod(
            $method,
        );

        return $method;
    }

    public function selectPaymentMethod(
        PaymentMethod $method,
    ): void {
        $this->session->put(
            self::PAYMENT_METHOD_KEY,
            $method->value,
        );
    }

    public function orderSubmissionToken(): string
    {
        $value = $this->session->get(
            self::ORDER_SUBMISSION_TOKEN_KEY,
        );

        if (
            is_string($value) &&
            $value !== ''
        ) {
            return $value;
        }

        return $this->rotateOrderSubmissionToken();
    }

    public function rotateOrderSubmissionToken(): string
    {
        $token = Str::uuid()
            ->toString();

        $this->session->put(
            self::ORDER_SUBMISSION_TOKEN_KEY,
            $token,
        );

        return $token;
    }
}
