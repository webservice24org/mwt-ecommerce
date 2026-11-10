<?php

declare(strict_types=1);

namespace App\Jobs\Shipping;

use App\Domain\Shipping\Actions\ReconcileShipmentProviderStatusAction;
use App\Domain\Shipping\Actions\SyncShipmentTrackingFromProviderAction;
use App\Models\Shipment;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncShipmentTrackingFromProviderJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly int $shipmentId,
    ) {
        $this->onQueue(
            'shipping',
        );
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [
            60,
            300,
            900,
            1800,
        ];
    }

    public function uniqueId(): string
    {
        return (string) $this->shipmentId;
    }

    public function handle(
        SyncShipmentTrackingFromProviderAction $syncTracking,
        ReconcileShipmentProviderStatusAction $reconcileStatus,
    ): void {
        $shipment = Shipment::query()
            ->find(
                $this->shipmentId,
            );

        if (! $shipment instanceof Shipment) {
            return;
        }

        $submission = $syncTracking->execute(
            $shipment,
        );

        $reconcileStatus->execute(
            $submission,
        );
    }
}
