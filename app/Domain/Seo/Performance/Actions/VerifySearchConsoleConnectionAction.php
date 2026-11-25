<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Actions;

use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Providers\SearchConsoleProviderAdapter;
use App\Domain\Seo\Performance\Services\SeoPerformanceConnectionManager;
use App\Models\SeoPerformanceConnection;
use DomainException;

final readonly class VerifySearchConsoleConnectionAction
{
    public function __construct(
        private SearchConsoleProviderAdapter $adapter,
        private SeoPerformanceConnectionManager $connections,
    ) {}

    public function execute(
        SeoPerformanceConnection $connection,
    ): SeoPerformanceConnection {
        if ($connection->provider !== SeoPerformanceProvider::SearchConsole) {
            throw new DomainException(
                'Only Google Search Console connections can be verified by this action.',
            );
        }

        $verification = $this->adapter->verify($connection);

        if (! $verification->verified) {
            return $this->connections->recordVerificationFailure(
                $connection,
                $verification->errorCode ?? 'verification_failed',
                $verification->message ?? 'Google Search Console verification failed.',
            );
        }

        return $this->connections->recordSuccessfulVerification($connection);
    }
}
