<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Performance;

use App\Domain\Seo\Performance\Actions\VerifyGoogleAnalyticsConnectionAction;
use App\Domain\Seo\Performance\Enums\SeoPerformanceConnectionStatus;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Services\SeoPerformanceConnectionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class GoogleAnalyticsConnectionVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_account_can_verify_google_analytics_property(): void
    {
        $this->configureCredentials();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'ga-test-token',
                'token_type' => 'Bearer',
            ]),
            'https://analyticsdata.googleapis.com/v1beta/properties/123456789:runReport' => Http::response([
                'dimensionHeaders' => [['name' => 'date']],
                'metricHeaders' => [['name' => 'sessions', 'type' => 'TYPE_INTEGER']],
                'rows' => [],
                'rowCount' => 0,
            ]),
        ]);

        $connection = app(SeoPerformanceConnectionManager::class)->register(
            SeoPerformanceProvider::GoogleAnalytics,
            'GA4 Production',
            '123456789',
            'https://example.com',
            'UTC',
        );

        $connection = app(VerifyGoogleAnalyticsConnectionAction::class)->execute($connection);

        self::assertSame(SeoPerformanceConnectionStatus::Active, $connection->status);
        self::assertNotNull($connection->verified_at);
        self::assertNull($connection->last_error_code);

        Http::assertSent(static function ($request): bool {
            return $request->url() === 'https://analyticsdata.googleapis.com/v1beta/properties/123456789:runReport'
                && $request['limit'] === 1
                && $request['metrics'][0]['name'] === 'sessions';
        });
    }

    public function test_denied_property_access_fails_closed_without_secret_leakage(): void
    {
        $this->configureCredentials();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'ga-test-token',
                'token_type' => 'Bearer',
            ]),
            'https://analyticsdata.googleapis.com/*' => Http::response([
                'error' => ['message' => 'permission denied SECRET-RAW'],
            ], 403),
        ]);

        $connection = app(SeoPerformanceConnectionManager::class)->register(
            SeoPerformanceProvider::GoogleAnalytics,
            'GA4 Production',
            '123456789',
            null,
            'UTC',
        );

        $connection = app(VerifyGoogleAnalyticsConnectionAction::class)->execute($connection);

        self::assertSame(SeoPerformanceConnectionStatus::Error, $connection->status);
        self::assertSame('authorization_denied', $connection->last_error_code);
        self::assertStringNotContainsString('SECRET-RAW', (string) $connection->last_error_message);
    }

    private function configureCredentials(): void
    {
        $privateKey = file_get_contents(
            base_path('tests/Fixtures/Seo/Performance/google-analytics-test-private-key.pem'),
        );
        self::assertIsString($privateKey);

        $path = storage_path('framework/testing/google-analytics-service-account.json');
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, json_encode([
            'type' => 'service_account',
            'client_email' => 'analytics-test@example.iam.gserviceaccount.com',
            'private_key_id' => 'test-key',
            'private_key' => $privateKey,
        ], JSON_THROW_ON_ERROR));

        config()->set(
            'seo_performance_google_analytics.service_account_json',
            $path,
        );
    }
}
