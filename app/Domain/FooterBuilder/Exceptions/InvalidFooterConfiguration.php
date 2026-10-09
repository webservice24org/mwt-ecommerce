<?php

declare(strict_types=1);

namespace App\Domain\FooterBuilder\Exceptions;

use RuntimeException;

final class InvalidFooterConfiguration extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        private readonly array $errors,
    ) {
        parent::__construct(
            'The footer configuration is invalid.',
        );
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
