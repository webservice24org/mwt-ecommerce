<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Operations;

use App\Domain\Reporting\Data\ActionQueueFiltersData;
use App\Domain\Reporting\Data\ActionQueueItemData;
use App\Domain\Reporting\Enums\ActionQueueSource;

interface ActionQueueProvider
{
    public function source(): ActionQueueSource;

    /**
     * @return array<int, ActionQueueItemData>
     */
    public function items(
        ActionQueueFiltersData $filters,
    ): array;
}
