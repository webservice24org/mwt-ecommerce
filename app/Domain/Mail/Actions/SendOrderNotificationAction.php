<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Mail\Data\OrderMailData;
use App\Mail\Orders\NewOrderAdminMail;
use App\Mail\Orders\OrderPlacedCustomerMail;
use App\Mail\Orders\OrderStatusChangedMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

final readonly class SendOrderNotificationAction
{
    public function __construct(
        private ConfigureRuntimeMailAction $configureRuntimeMail,
    ) {}

    public function sendCustomerOrderPlaced(
        Order $order,
    ): void {
        $settings =
            $this
                ->configureRuntimeMail
                ->execute();

        if (! $settings->enabled) {
            return;
        }

        $order->loadMissing([
            'user',
            'items',
            'invoice',
        ]);

        $data =
            OrderMailData::fromOrder(
                order: $order,

                invoiceUrl: $order->invoice !== null
                ? route(
                    'customer.invoices.show',
                    $order->invoice,
                )
                : null,
            );

        Mail::mailer(
            $settings->mailer,
        )
            ->to(
                $order->user->email,
            )
            ->queue(
                new OrderPlacedCustomerMail(
                    $data,
                ),
            );
    }

    public function sendAdminNewOrder(
        Order $order,
    ): void {
        $settings =
            $this
                ->configureRuntimeMail
                ->execute();

        if (
            ! $settings->enabled
            || blank(
                $settings
                    ->adminOrderNotificationEmail,
            )
        ) {
            return;
        }

        $order->loadMissing([
            'user',
            'items',
            'invoice',
        ]);

        $data =
            OrderMailData::fromOrder(
                order: $order,

                invoiceUrl: $order->invoice !== null
                ? route(
                    'admin.invoices.show',
                    $order->invoice,
                )
                : null,
            );

        Mail::mailer(
            $settings->mailer,
        )
            ->to(
                $settings
                    ->adminOrderNotificationEmail,
            )
            ->queue(
                new NewOrderAdminMail(
                    order: $data,

                    adminOrderUrl: route(
                        'admin.orders.show',
                        $order,
                    ),
                ),
            );
    }

    public function sendCustomerStatusChanged(
        Order $order,
        string $headline,
        string $statusMessage,
    ): void {
        $settings =
            $this
                ->configureRuntimeMail
                ->execute();

        if (! $settings->enabled) {
            return;
        }

        $order->loadMissing([
            'user',
            'items',
            'invoice',
        ]);

        $data =
            OrderMailData::fromOrder(
                order: $order,

                invoiceUrl: $order->invoice !== null
                ? route(
                    'customer.invoices.show',
                    $order->invoice,
                )
                : null,
            );

        Mail::mailer(
            $settings->mailer,
        )
            ->to(
                $order->user->email,
            )
            ->queue(
                new OrderStatusChangedMail(
                    order: $data,

                    headline: $headline,

                    statusMessage: $statusMessage,
                ),
            );
    }
}
