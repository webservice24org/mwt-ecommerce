<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Providers\PathaoShippingProvider;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Domain\Shipping\Queries\GetAdminOrderShippingReadModelQuery;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

final class ShippingProviderFinalSecurityQaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_shipping_read_model_never_exposes_provider_credentials(): void
    {
        config()->set('shipping.providers.pathao.enabled', true);
        config()->set('shipping.providers.pathao.base_url', 'https://pathao.test');
        config()->set('shipping.providers.pathao.access_token', 'TOP-SECRET-ACCESS-TOKEN');
        config()->set('shipping.providers.pathao.client_secret', 'TOP-SECRET-CLIENT-SECRET');
        config()->set('shipping.providers.pathao.password', 'TOP-SECRET-PASSWORD');
        config()->set('shipping.providers.pathao.store_id', 12345);

        app()->forgetInstance(PathaoShippingProvider::class);
        app()->forgetInstance(ShippingProviderRegistry::class);

        $user = User::factory()->create();
        $method = ShippingMethod::factory()->create([
            'driver' => 'pathao',
            'is_active' => true,
        ]);

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Processing,
                'fulfilment_status' => FulfilmentStatus::Processing,
                'shipping_method_id' => $method->id,
                'shipping_method_code' => $method->code,
                'shipping_method_name' => $method->name,
            ]);

        Shipment::factory()
            ->for($order)
            ->for($method, 'shippingMethod')
            ->create([
                'status' => ShipmentStatus::Processing,
            ]);

        $json = json_encode(
            app(GetAdminOrderShippingReadModelQuery::class)->get($order),
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString('TOP-SECRET-ACCESS-TOKEN', $json);
        $this->assertStringNotContainsString('TOP-SECRET-CLIENT-SECRET', $json);
        $this->assertStringNotContainsString('TOP-SECRET-PASSWORD', $json);
    }

    public function test_pathao_base_url_must_be_https_without_embedded_credentials(): void
    {
        Http::fake();

        $provider = new PathaoShippingProvider(
            baseUrl: 'http://user:password@pathao.test',
            staticAccessToken: 'token',
            clientId: null,
            clientSecret: null,
            username: null,
            password: null,
            storeId: 123,
        );

        $this->expectException(RuntimeException::class);

        try {
            $provider->refreshTracking(
                Shipment::factory()->make(),
                'TEST-CONSIGNMENT',
            );
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_pathao_webhook_boundary_remains_fail_closed_until_signature_contract_is_verified(): void
    {
        $this->assertFalse(
            (bool) config(
                'shipping.providers.pathao.webhook.accept_inbound',
                true,
            ),
        );

        $this->assertFalse(
            Route::has('webhooks.shipping.pathao'),
        );
    }

    public function test_admin_tracking_link_keeps_noopener_and_noreferrer(): void
    {
        $source = file_get_contents(
            resource_path('js/Pages/Admin/Orders/Show.tsx'),
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'rel="noopener noreferrer"',
            $source,
        );
    }
}
