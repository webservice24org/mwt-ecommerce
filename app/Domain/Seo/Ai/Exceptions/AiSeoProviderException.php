<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Exceptions;

use RuntimeException;
use Throwable;

final class AiSeoProviderException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            $message,
            0,
            $previous,
        );
    }
}
