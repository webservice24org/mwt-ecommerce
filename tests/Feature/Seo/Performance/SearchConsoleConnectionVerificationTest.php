<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Performance;

use App\Domain\Seo\Performance\Actions\VerifySearchConsoleConnectionAction;
use App\Domain\Seo\Performance\Enums\SeoPerformanceConnectionStatus;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Services\SearchConsoleApiClient;
use App\Domain\Seo\Performance\Services\SearchConsoleServiceAccountAccessTokenProvider;
use App\Domain\Seo\Performance\Services\SeoPerformanceConnectionManager;
use App\Models\SeoPerformanceConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class SearchConsoleConnectionVerificationTest extends TestCase
{
    use RefreshDatabase;

    private ?string $credentialFile = null;

    protected function tearDown(): void
    {
        if ($this->credentialFile !== null && is_file($this->credentialFile)) {
            @unlink($this->credentialFile);
        }

        parent::tearDown();
    }

    public function test_service_account_can_verify_exact_search_console_property(): void
    {
        $this->configureCredentials();
        $property = 'sc-domain:example.com';

        Http::fake([
            SearchConsoleServiceAccountAccessTokenProvider::TOKEN_URI => Http::response([
                'access_token' => 'test-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            SearchConsoleApiClient::SITES_URI.'/*' => Http::response([
                'siteUrl' => $property,
                'permissionLevel' => 'siteOwner',
            ]),
        ]);

        $connection = $this->connection($property);
        $verified = app(VerifySearchConsoleConnectionAction::class)->execute($connection);

        self::assertSame(SeoPerformanceConnectionStatus::Active, $verified->status);
        self::assertNotNull($verified->verified_at);
        self::assertNull($verified->last_error_code);

        Http::assertSent(static function (Request $request): bool {
            if ($request->url() !== SearchConsoleServiceAccountAccessTokenProvider::TOKEN_URI) {
                return false;
            }

            $assertion = $request->data()['assertion'] ?? null;

            return $request->method() === 'POST'
                && ($request->data()['grant_type'] ?? null) === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
                && is_string($assertion)
                && substr_count($assertion, '.') === 2;
        });

        Http::assertSent(static function (Request $request) use ($property): bool {
            return $request->method() === 'GET'
                && $request->url() === SearchConsoleApiClient::SITES_URI.'/'.rawurlencode($property)
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
    }

    public function test_unverified_property_fails_closed(): void
    {
        $this->configureCredentials();
        $property = 'https://www.example.com/';

        Http::fake([
            SearchConsoleServiceAccountAccessTokenProvider::TOKEN_URI => Http::response([
                'access_token' => 'test-access-token',
                'token_type' => 'Bearer',
            ]),
            SearchConsoleApiClient::SITES_URI.'/*' => Http::response([
                'siteUrl' => $property,
                'permissionLevel' => 'siteUnverifiedUser',
            ]),
        ]);

        $verified = app(VerifySearchConsoleConnectionAction::class)
            ->execute($this->connection($property));

        self::assertSame(SeoPerformanceConnectionStatus::Error, $verified->status);
        self::assertSame('property_unverified', $verified->last_error_code);
        self::assertNull($verified->verified_at);
    }

    public function test_missing_credentials_are_recorded_without_leaking_secrets(): void
    {
        config()->set('seo_performance.search_console.service_account_json', null);
        Http::fake();

        $verified = app(VerifySearchConsoleConnectionAction::class)
            ->execute($this->connection('sc-domain:example.com'));

        self::assertSame(SeoPerformanceConnectionStatus::Error, $verified->status);
        self::assertSame('credentials_unavailable', $verified->last_error_code);
        self::assertStringNotContainsString('private', strtolower((string) $verified->last_error_message));
        Http::assertNothingSent();
    }

    private function connection(string $property): SeoPerformanceConnection
    {
        return app(SeoPerformanceConnectionManager::class)->register(
            provider: SeoPerformanceProvider::SearchConsole,
            displayName: 'Primary Search Console',
            propertyIdentifier: $property,
            propertyUrl: str_starts_with($property, 'http') ? $property : 'https://example.com/',
            timezone: 'UTC',
        );
    }

    private function configureCredentials(): void
    {
        $fixture = dirname(__DIR__, 3).'/Fixtures/Seo/Performance/search-console-test-private-key.pem';
        $privateKey = file_get_contents($fixture);

        if (! is_string($privateKey) || trim($privateKey) === '') {
            throw new RuntimeException('Unable to read the Search Console test key fixture.');
        }

        $file = tempnam(sys_get_temp_dir(), 'mwt-gsc-');
        if ($file === false) {
            throw new RuntimeException('Unable to create a Search Console credential fixture.');
        }

        file_put_contents($file, json_encode([
            'type' => 'service_account',
            'private_key_id' => 'test-key-id',
            'private_key' => $privateKey,
            'client_email' => 'seo-test@example-project.iam.gserviceaccount.com',
        ], JSON_THROW_ON_ERROR));

        $this->credentialFile = $file;
        config()->set('seo_performance.search_console.service_account_json', $file);
    }
}
