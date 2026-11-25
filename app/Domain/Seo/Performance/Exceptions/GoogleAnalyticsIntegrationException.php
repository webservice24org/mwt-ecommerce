<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Exceptions;

use RuntimeException;

final class GoogleAnalyticsIntegrationException extends RuntimeException
{
    public function __construct(
        public readonly string $failureCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
