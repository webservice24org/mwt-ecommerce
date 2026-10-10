<?php

declare(strict_types=1);

namespace App\Domain\HeaderBuilder\Exceptions;

use RuntimeException;

final class InvalidHeaderConfiguration extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        public readonly array $errors,
    ) {
        parent::__construct(
            'The header configuration is invalid.',
        );
    }
}
