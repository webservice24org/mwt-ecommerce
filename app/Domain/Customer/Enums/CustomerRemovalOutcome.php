<?php

declare(strict_types=1);

namespace App\Domain\Customer\Enums;

enum CustomerRemovalOutcome: string
{
    case Deleted = 'deleted';

    case Anonymized = 'anonymized';
}
