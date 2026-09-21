<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections\Exceptions;

use InvalidArgumentException;

final class InvalidSectionConfiguration extends InvalidArgumentException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        private readonly array $errors,
    ) {
        parent::__construct(
            'The Page Builder section configuration is invalid.',
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
