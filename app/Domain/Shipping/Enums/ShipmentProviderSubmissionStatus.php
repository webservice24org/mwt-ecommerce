<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Enums;

enum ShipmentProviderSubmissionStatus: string
{
    case Pending = 'pending';
    case Submitting = 'submitting';
    case Submitted = 'submitted';
    case Failed = 'failed';
    case ReviewRequired = 'review_required';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Submitting => 'Submitting',
            self::Submitted => 'Submitted',
            self::Failed => 'Failed',
            self::ReviewRequired => 'Review required',
        };
    }
}
