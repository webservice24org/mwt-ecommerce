<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Orders;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OrderStateTransitionTest extends TestCase
{
    /**
     * @return array<
     *     string,
     *     array{
     *         OrderStatus,
     *         OrderStatus
     *     }
     * >
     */
    public static function allowedOrderTransitions(): array
    {
        return [
            'pending to confirmed' => [
                OrderStatus::Pending,
                OrderStatus::Confirmed,
            ],

            'pending to cancelled' => [
                OrderStatus::Pending,
                OrderStatus::Cancelled,
            ],

            'confirmed to processing' => [
                OrderStatus::Confirmed,
                OrderStatus::Processing,
            ],

            'confirmed to cancelled' => [
                OrderStatus::Confirmed,
                OrderStatus::Cancelled,
            ],

            'processing to completed' => [
                OrderStatus::Processing,
                OrderStatus::Completed,
            ],
        ];
    }

    #[DataProvider(
        'allowedOrderTransitions',
    )]
    public function test_allowed_order_transitions(
        OrderStatus $from,
        OrderStatus $to,
    ): void {
        $this->assertTrue(
            $from->canTransitionTo(
                $to,
            ),
        );
    }

    /**
     * @return array<
     *     string,
     *     array{
     *         OrderStatus,
     *         OrderStatus
     *     }
     * >
     */
    public static function forbiddenOrderTransitions(): array
    {
        return [
            'pending directly to processing' => [
                OrderStatus::Pending,
                OrderStatus::Processing,
            ],

            'pending directly to completed' => [
                OrderStatus::Pending,
                OrderStatus::Completed,
            ],

            'confirmed back to pending' => [
                OrderStatus::Confirmed,
                OrderStatus::Pending,
            ],

            'processing back to confirmed' => [
                OrderStatus::Processing,
                OrderStatus::Confirmed,
            ],

            'processing to cancelled' => [
                OrderStatus::Processing,
                OrderStatus::Cancelled,
            ],

            'completed back to processing' => [
                OrderStatus::Completed,
                OrderStatus::Processing,
            ],

            'cancelled back to pending' => [
                OrderStatus::Cancelled,
                OrderStatus::Pending,
            ],

            'same pending state' => [
                OrderStatus::Pending,
                OrderStatus::Pending,
            ],
        ];
    }

    #[DataProvider(
        'forbiddenOrderTransitions',
    )]
    public function test_forbidden_order_transitions(
        OrderStatus $from,
        OrderStatus $to,
    ): void {
        $this->assertFalse(
            $from->canTransitionTo(
                $to,
            ),
        );
    }

    public function test_completed_and_cancelled_orders_are_terminal(): void
    {
        $this->assertTrue(
            OrderStatus::Completed
                ->isTerminal(),
        );

        $this->assertTrue(
            OrderStatus::Cancelled
                ->isTerminal(),
        );

        $this->assertFalse(
            OrderStatus::Pending
                ->isTerminal(),
        );
    }

    public function test_fulfilment_transition_contract(): void
    {
        $this->assertTrue(
            FulfilmentStatus::Unfulfilled
                ->canTransitionTo(
                    FulfilmentStatus::Processing,
                ),
        );

        $this->assertTrue(
            FulfilmentStatus::Processing
                ->canTransitionTo(
                    FulfilmentStatus::Shipped,
                ),
        );

        $this->assertTrue(
            FulfilmentStatus::Shipped
                ->canTransitionTo(
                    FulfilmentStatus::Delivered,
                ),
        );

        $this->assertTrue(
            FulfilmentStatus::Delivered
                ->canTransitionTo(
                    FulfilmentStatus::Returned,
                ),
        );

        $this->assertFalse(
            FulfilmentStatus::Delivered
                ->canTransitionTo(
                    FulfilmentStatus::Processing,
                ),
        );
    }

    public function test_payment_transition_contract(): void
    {
        /*
         * COD may move directly from unpaid to paid
         * when payment is collected.
         */
        $this->assertTrue(
            PaymentStatus::Unpaid
                ->canTransitionTo(
                    PaymentStatus::Paid,
                ),
        );

        /*
         * Gateway payments may use pending first.
         */
        $this->assertTrue(
            PaymentStatus::Unpaid
                ->canTransitionTo(
                    PaymentStatus::Pending,
                ),
        );

        $this->assertTrue(
            PaymentStatus::Pending
                ->canTransitionTo(
                    PaymentStatus::Paid,
                ),
        );

        $this->assertTrue(
            PaymentStatus::Paid
                ->canTransitionTo(
                    PaymentStatus::PartiallyRefunded,
                ),
        );

        $this->assertTrue(
            PaymentStatus::Paid
                ->canTransitionTo(
                    PaymentStatus::Refunded,
                ),
        );

        $this->assertFalse(
            PaymentStatus::Refunded
                ->canTransitionTo(
                    PaymentStatus::Paid,
                ),
        );
    }
}
