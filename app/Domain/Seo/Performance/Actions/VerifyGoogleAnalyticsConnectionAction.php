<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Actions;

use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Providers\GoogleAnalyticsProviderAdapter;
use App\Domain\Seo\Performance\Services\SeoPerformanceConnectionManager;
use App\Models\SeoPerformanceConnection;
use DomainException;

final readonly class VerifyGoogleAnalyticsConnectionAction
{
    public function __construct(
        private GoogleAnalyticsProviderAdapter $adapter,
        private SeoPerformanceConnectionManager $connections,
    ) {}

    public function execute(
        SeoPerformanceConnection $connection,
    ): SeoPerformanceConnection {
        if ($connection->provider !== SeoPerformanceProvider::GoogleAnalytics) {
            throw new DomainException(
                'Only Google Analytics connections can be verified by this action.',
            );
        }

        $verification = $this->adapter->verify($connection);

        if (! $verification->verified) {
            return $this->connections->recordVerificationFailure(
                $connection,
                $verification->errorCode ?? 'verification_failed',
                $verification->message ?? 'Google Analytics verification failed.',
            );
        }

        return $this->connections->recordSuccessfulVerification($connection);
    }
}
