<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Exceptions;

use RuntimeException;

final class SeoAuditFetchException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
