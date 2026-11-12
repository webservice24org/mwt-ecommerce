<?php

declare(strict_types=1);

namespace Tests\Unit\Cart;

use App\Domain\Cart\Services\CartPromoSession;
use InvalidArgumentException;
use Tests\TestCase;

final class CartPromoSessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        session()->flush();
    }

    protected function tearDown(): void
    {
        session()->flush();

        parent::tearDown();
    }

    public function test_session_has_no_promo_code_by_default(): void
    {
        $session =
            new CartPromoSession;

        $this->assertNull(
            $session->promoCodeId(),
        );

        $this->assertFalse(
            $session->has(),
        );
    }

    public function test_promo_code_id_can_be_stored(): void
    {
        $session =
            new CartPromoSession;

        $session->put(
            25,
        );

        $this->assertSame(
            25,
            $session->promoCodeId(),
        );

        $this->assertTrue(
            $session->has(),
        );

        $this->assertSame(
            25,
            session()->get(
                'storefront.selected_promo_code_id',
            ),
        );
    }

    public function test_numeric_string_session_value_is_normalized_to_integer(): void
    {
        session()->put(
            'storefront.selected_promo_code_id',
            '42',
        );

        $session =
            new CartPromoSession;

        $this->assertSame(
            42,
            $session->promoCodeId(),
        );
    }

    public function test_invalid_session_value_is_treated_as_missing(): void
    {
        session()->put(
            'storefront.selected_promo_code_id',
            'invalid',
        );

        $session =
            new CartPromoSession;

        $this->assertNull(
            $session->promoCodeId(),
        );

        $this->assertFalse(
            $session->has(),
        );
    }

    public function test_zero_session_value_is_treated_as_missing(): void
    {
        session()->put(
            'storefront.selected_promo_code_id',
            0,
        );

        $session =
            new CartPromoSession;

        $this->assertNull(
            $session->promoCodeId(),
        );
    }

    public function test_negative_session_value_is_treated_as_missing(): void
    {
        session()->put(
            'storefront.selected_promo_code_id',
            -10,
        );

        $session =
            new CartPromoSession;

        $this->assertNull(
            $session->promoCodeId(),
        );
    }

    public function test_put_rejects_zero_id(): void
    {
        $session =
            new CartPromoSession;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $session->put(
            0,
        );
    }

    public function test_put_rejects_negative_id(): void
    {
        $session =
            new CartPromoSession;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $session->put(
            -1,
        );
    }

    public function test_promo_code_can_be_removed(): void
    {
        $session =
            new CartPromoSession;

        $session->put(
            15,
        );

        $this->assertTrue(
            $session->has(),
        );

        $session->forget();

        $this->assertNull(
            $session->promoCodeId(),
        );

        $this->assertFalse(
            $session->has(),
        );

        $this->assertNull(
            session()->get(
                'storefront.selected_promo_code_id',
            ),
        );
    }

    public function test_forget_is_idempotent(): void
    {
        $session =
            new CartPromoSession;

        $session->forget();
        $session->forget();

        $this->assertNull(
            $session->promoCodeId(),
        );

        $this->assertFalse(
            $session->has(),
        );
    }

    public function test_promo_session_does_not_pollute_cart_item_session(): void
    {
        session()->put(
            'storefront.cart',
            [
                'product:10' => [
                    'product_id' => 10,

                    'variant_id' => null,

                    'quantity' => 2,
                ],
            ],
        );

        $session =
            new CartPromoSession;

        $session->put(
            99,
        );

        $this->assertSame(
            [
                'product:10' => [
                    'product_id' => 10,

                    'variant_id' => null,

                    'quantity' => 2,
                ],
            ],
            session()->get(
                'storefront.cart',
            ),
        );

        $this->assertSame(
            99,
            session()->get(
                'storefront.selected_promo_code_id',
            ),
        );
    }

    public function test_removing_promo_does_not_remove_cart_items(): void
    {
        session()->put(
            'storefront.cart',
            [
                'product:7' => [
                    'product_id' => 7,

                    'variant_id' => null,

                    'quantity' => 1,
                ],
            ],
        );

        $session =
            new CartPromoSession;

        $session->put(
            50,
        );

        $session->forget();

        $this->assertNull(
            $session->promoCodeId(),
        );

        $this->assertSame(
            [
                'product:7' => [
                    'product_id' => 7,

                    'variant_id' => null,

                    'quantity' => 1,
                ],
            ],
            session()->get(
                'storefront.cart',
            ),
        );
    }
}
