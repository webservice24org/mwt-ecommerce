<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Order;
use InvalidArgumentException;

final readonly class SendOrderStatusNotificationAction
{
    public function __construct(
        private SendOrderNotificationAction $notifications,
    ) {}

    public function confirmed(
        Order $order,
    ): void {
        $this->assertOrderStatus(
            $order,
            OrderStatus::Confirmed,
        );

        $this
            ->notifications
            ->sendCustomerStatusChanged(
                order: $order,

                headline: 'Your order has been confirmed',

                statusMessage: 'We have confirmed your order and it will be prepared for processing shortly.',
            );
    }

    public function processing(
        Order $order,
    ): void {
        $this->assertOrderStatus(
            $order,
            OrderStatus::Processing,
        );

        $this->assertFulfilmentStatus(
            $order,
            FulfilmentStatus::Processing,
        );

        $this
            ->notifications
            ->sendCustomerStatusChanged(
                order: $order,

                headline: 'We are processing your order',

                statusMessage: 'Your order is now being prepared for fulfilment.',
            );
    }

    public function shipped(
        Order $order,
    ): void {
        $this->assertFulfilmentStatus(
            $order,
            FulfilmentStatus::Shipped,
        );

        $this
            ->notifications
            ->sendCustomerStatusChanged(
                order: $order,

                headline: 'Your order has been shipped',

                statusMessage: 'Your order has left our fulfilment process and is now on the way to you.',
            );
    }

    public function delivered(
        Order $order,
    ): void {
        $this->assertFulfilmentStatus(
            $order,
            FulfilmentStatus::Delivered,
        );

        $this
            ->notifications
            ->sendCustomerStatusChanged(
                order: $order,

                headline: 'Your order has been delivered',

                statusMessage: 'Your order has been marked as delivered. Thank you for shopping with us.',
            );
    }

    public function cancelled(
        Order $order,
        ?string $reason = null,
    ): void {
        $this->assertOrderStatus(
            $order,
            OrderStatus::Cancelled,
        );

        $message =
            'Your order has been cancelled.';

        if (
            is_string($reason)
            && trim($reason) !== ''
        ) {
            $message .= sprintf(
                ' Reason: %s',
                trim($reason),
            );
        }

        $this
            ->notifications
            ->sendCustomerStatusChanged(
                order: $order,

                headline: 'Your order has been cancelled',

                statusMessage: $message,
            );
    }

    private function assertOrderStatus(
        Order $order,
        OrderStatus $expected,
    ): void {
        if (
            $order->status !==
            $expected
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Expected order status [%s], got [%s].',
                    $expected->value,
                    $order->status->value,
                ),
            );
        }
    }

    private function assertFulfilmentStatus(
        Order $order,
        FulfilmentStatus $expected,
    ): void {
        if (
            $order->fulfilment_status !==
            $expected
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Expected fulfilment status [%s], got [%s].',
                    $expected->value,
                    $order->fulfilment_status->value,
                ),
            );
        }
    }
}
