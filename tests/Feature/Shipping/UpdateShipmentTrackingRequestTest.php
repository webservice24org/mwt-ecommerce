<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Http\Requests\Admin\Orders\UpdateShipmentTrackingRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class UpdateShipmentTrackingRequestTest extends TestCase
{
    public function test_tracking_fields_require_carrier(): void
    {
        $rules = (new UpdateShipmentTrackingRequest)->rules();

        foreach ([
            ['service' => 'Express'],
            ['tracking_number' => 'TRACK-100'],
            ['tracking_url' => 'https://tracking.example.test/TRACK-100'],
        ] as $payload) {
            $validator = Validator::make($payload, $rules);

            $this->assertTrue($validator->fails());
            $this->assertArrayHasKey('carrier', $validator->errors()->toArray());
        }
    }

    public function test_carrier_only_is_valid(): void
    {
        $validator = Validator::make(
            ['carrier' => 'Own Delivery Team'],
            (new UpdateShipmentTrackingRequest)->rules(),
        );

        $this->assertFalse($validator->fails());
    }

    public function test_tracking_url_must_use_http_or_https(): void
    {
        $validator = Validator::make(
            [
                'carrier' => 'Courier',
                'tracking_url' => 'javascript:alert(1)',
            ],
            (new UpdateShipmentTrackingRequest)->rules(),
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tracking_url', $validator->errors()->toArray());
    }
}
