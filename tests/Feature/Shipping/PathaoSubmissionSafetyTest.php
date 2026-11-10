<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Shipping\Actions\SubmitShipmentToProviderAction;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Exceptions\ProviderSubmissionOutcomeUnknownException;
use App\Domain\Shipping\Providers\PathaoShippingProvider;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentProviderSubmission;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PathaoSubmissionSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_ambiguous_pathao_submission_enters_review_required_and_cannot_retry(): void
    {
        Http::fake([
            'https://pathao.test/aladdin/api/v1/orders' => Http::response(
                [],
                504,
            ),
        ]);

        $provider =
            new PathaoShippingProvider(
                baseUrl: 'https://pathao.test',

                staticAccessToken: 'token',

                clientId: null,

                clientSecret: null,

                username: null,

                password: null,

                storeId: 12345,
            );

        $registry =
            new ShippingProviderRegistry;

        $registry->register(
            $provider,
        );

        $this->app->instance(
            ShippingProviderRegistry::class,
            $registry,
        );

        $shipment =
            $this->shipment();

        $action = app(
            SubmitShipmentToProviderAction::class,
        );

        try {
            $action->execute(
                $shipment,
            );

            $this->fail(
                'Pathao 5xx create outcome should require manual review.',
            );
        } catch (
            ProviderSubmissionOutcomeUnknownException
        ) {
            //
        }

        $submission =
            ShipmentProviderSubmission::query()
                ->where(
                    'shipment_id',
                    $shipment->id,
                )
                ->firstOrFail();

        $this->assertSame(
            ShipmentProviderSubmissionStatus::ReviewRequired,
            $submission->status,
        );

        $this->assertSame(
            'Provider submission outcome is uncertain. Manual provider review is required before retry.',
            $submission->last_error,
        );

        $this->expectException(
            ValidationException::class,
        );

        $action->execute(
            $shipment->refresh(),
        );
    }

    private function shipment(): Shipment
    {
        $user = User::factory()
            ->create();

        $method =
            ShippingMethod::factory()
                ->create([
                    'driver' => PathaoShippingProvider::CODE,

                    'is_active' => true,
                ]);

        $order = Order::factory()
            ->for(
                $user,
            )
            ->create([
                'status' => OrderStatus::Processing,

                'payment_method' => PaymentMethod::CashOnDelivery,

                'payment_status' => PaymentStatus::Unpaid,

                'fulfilment_status' => FulfilmentStatus::Processing,

                'shipping_method_id' => $method->id,

                'shipping_method_code' => $method->code,

                'shipping_method_name' => $method->name,

                'currency_code' => 'BDT',

                'grand_total' => 100000,

                'delivery_recipient_name' => 'Customer',

                'delivery_phone' => '01712345678',

                'delivery_address_line_1' => 'House 10, Road 5',

                'delivery_city' => 'Dhaka',

                'delivery_country_code' => 'BD',
            ]);

        $orderItem = OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'product_name' => 'Product',

                'quantity' => 1,
            ]);

        $shipment = Shipment::factory()
            ->for(
                $order,
            )
            ->for(
                $method,
                'shippingMethod',
            )
            ->create([
                'status' => ShipmentStatus::Processing,
            ]);

        ShipmentItem::factory()
            ->for(
                $shipment,
            )
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 1,
            ]);

        return $shipment;
    }
}
