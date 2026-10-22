<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Models\Order;

final readonly class SendOrderPlacedNotificationsAction
{
    public function __construct(
        private SendOrderNotificationAction $notifications,
    ) {}

    public function execute(
        Order $order,
    ): void {
        $this->notifications
            ->sendCustomerOrderPlaced(
                $order,
            );

        $this->notifications
            ->sendAdminNewOrder(
                $order,
            );
    }
}
