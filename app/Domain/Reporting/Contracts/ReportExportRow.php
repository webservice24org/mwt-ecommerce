<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Contracts;

interface ReportExportRow
{
    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array;
}
