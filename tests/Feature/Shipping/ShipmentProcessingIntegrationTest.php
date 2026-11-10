<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Actions\ConfirmOrderAction;
use App\Domain\Orders\Actions\StartOrderProcessingAction;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Actions\StartShipmentProcessingAction;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ShipmentProcessingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_confirmed_order_enters_processing_when_shipment_processing_starts(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Confirmed,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        $shippingMethod =
            ShippingMethod::factory()
                ->create();

        $shipment =
            Shipment::factory()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create([
                    'status' => ShipmentStatus::Pending,
                ]);

        $processedShipment = app(
            StartShipmentProcessingAction::class,
        )->execute(
            shipment: $shipment,

            actor: $user,

            note: 'Warehouse started preparing shipment.',
        );

        $order->refresh();

        $this->assertSame(
            ShipmentStatus::Processing,
            $processedShipment->status,
        );

        $this->assertSame(
            OrderStatus::Processing,
            $order->status,
        );

        $this->assertSame(
            FulfilmentStatus::Processing,
            $order->fulfilment_status,
        );

        /*
         * Invoice remains owned by
         * StartOrderProcessingAction.
         */
        $this->assertDatabaseHas(
            'invoices',
            [
                'order_id' => $order->id,
            ],
        );

        $this->assertDatabaseCount(
            'invoices',
            1,
        );

        $this->assertDatabaseHas(
            'shipments',
            [
                'id' => $shipment->id,

                'status' => ShipmentStatus::Processing
                    ->value,
            ],
        );
    }

    public function test_existing_processing_order_does_not_repeat_order_transition(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Pending,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        $order = app(
            ConfirmOrderAction::class,
        )->execute(
            order: $order,

            actor: $user,

            note: 'Confirmed.',
        );

        $order = app(
            StartOrderProcessingAction::class,
        )->execute(
            order: $order,

            actor: $user,

            note: 'Processing started.',
        );

        $historyCountBefore =
            $order
                ->statusHistories()
                ->count();

        $invoiceCountBefore =
            $order
                ->invoice()
                ->count();

        $shippingMethod =
            ShippingMethod::factory()
                ->create();

        $shipment =
            Shipment::factory()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create([
                    'status' => ShipmentStatus::Pending,
                ]);

        $processedShipment = app(
            StartShipmentProcessingAction::class,
        )->execute(
            shipment: $shipment,

            actor: $user,
        );

        $order->refresh();

        $this->assertSame(
            ShipmentStatus::Processing,
            $processedShipment->status,
        );

        $this->assertSame(
            $historyCountBefore,
            $order
                ->statusHistories()
                ->count(),
        );

        $this->assertSame(
            $invoiceCountBefore,
            $order
                ->invoice()
                ->count(),
        );
    }

    public function test_pending_order_cannot_start_shipment_processing(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Pending,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        $shippingMethod =
            ShippingMethod::factory()
                ->create();

        $shipment =
            Shipment::factory()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create([
                    'status' => ShipmentStatus::Pending,
                ]);

        try {
            app(
                StartShipmentProcessingAction::class,
            )->execute(
                shipment: $shipment,

                actor: $user,
            );

            $this->fail(
                'Pending order should not start shipment processing.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'status',
                $exception->errors(),
            );
        }

        $shipment->refresh();
        $order->refresh();

        $this->assertSame(
            ShipmentStatus::Pending,
            $shipment->status,
        );

        $this->assertSame(
            OrderStatus::Pending,
            $order->status,
        );

        $this->assertDatabaseCount(
            'invoices',
            0,
        );
    }

    public function test_cancelled_shipment_cannot_enter_processing(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Processing,

                'fulfilment_status' => FulfilmentStatus::Processing,
            ]);

        $shippingMethod =
            ShippingMethod::factory()
                ->create();

        $shipment =
            Shipment::factory()
                ->cancelled()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        try {
            app(
                StartShipmentProcessingAction::class,
            )->execute(
                shipment: $shipment,

                actor: $user,
            );

            $this->fail(
                'Cancelled shipment must not re-enter processing.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'shipment_status',
                $exception->errors(),
            );
        }

        $shipment->refresh();

        $this->assertSame(
            ShipmentStatus::Cancelled,
            $shipment->status,
        );
    }

    public function test_shipped_shipment_cannot_return_to_processing(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Processing,

                'fulfilment_status' => FulfilmentStatus::Processing,
            ]);

        $shippingMethod =
            ShippingMethod::factory()
                ->create();

        $shipment =
            Shipment::factory()
                ->shipped()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        $this->expectException(
            ValidationException::class,
        );

        app(
            StartShipmentProcessingAction::class,
        )->execute(
            shipment: $shipment,

            actor: $user,
        );
    }

    public function test_processing_multiple_shipments_does_not_duplicate_invoice(): void
    {
        $user = User::factory()
            ->create();

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Confirmed,

                'fulfilment_status' => FulfilmentStatus::Unfulfilled,
            ]);

        $shippingMethod =
            ShippingMethod::factory()
                ->create();

        $firstShipment =
            Shipment::factory()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        $secondShipment =
            Shipment::factory()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create();

        $action = app(
            StartShipmentProcessingAction::class,
        );

        $action->execute(
            shipment: $firstShipment,

            actor: $user,
        );

        $action->execute(
            shipment: $secondShipment,

            actor: $user,
        );

        $firstShipment->refresh();
        $secondShipment->refresh();
        $order->refresh();

        $this->assertSame(
            ShipmentStatus::Processing,
            $firstShipment->status,
        );

        $this->assertSame(
            ShipmentStatus::Processing,
            $secondShipment->status,
        );

        $this->assertSame(
            OrderStatus::Processing,
            $order->status,
        );

        $this->assertSame(
            FulfilmentStatus::Processing,
            $order->fulfilment_status,
        );

        $this->assertDatabaseCount(
            'invoices',
            1,
        );
    }
}
