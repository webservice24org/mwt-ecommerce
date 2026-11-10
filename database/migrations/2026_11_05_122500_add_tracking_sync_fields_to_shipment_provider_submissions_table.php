<?php

declare(strict_types=1);

use App\Domain\Shipping\Enums\ShipmentProviderTrackingSyncStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'shipment_provider_submissions',
            function (Blueprint $table): void {
                $table->string(
                    'tracking_sync_status',
                    32,
                )
                    ->default(
                        ShipmentProviderTrackingSyncStatus::Pending
                            ->value,
                    )
                    ->after(
                        'submitted_at',
                    )
                    ->index();

                $table->unsignedInteger(
                    'tracking_sync_attempt_count',
                )
                    ->default(
                        0,
                    )
                    ->after(
                        'tracking_sync_status',
                    );

                $table->string(
                    'provider_status',
                    120,
                )
                    ->nullable()
                    ->after(
                        'tracking_sync_attempt_count',
                    )
                    ->index();

                $table->timestamp(
                    'last_tracking_sync_attempt_at',
                )
                    ->nullable()
                    ->after(
                        'provider_status',
                    );

                $table->timestamp(
                    'last_tracking_synced_at',
                )
                    ->nullable()
                    ->after(
                        'last_tracking_sync_attempt_at',
                    );

                $table->string(
                    'last_tracking_sync_error',
                    255,
                )
                    ->nullable()
                    ->after(
                        'last_tracking_synced_at',
                    );
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'shipment_provider_submissions',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'tracking_sync_status',
                ]);

                $table->dropIndex([
                    'provider_status',
                ]);

                $table->dropColumn([
                    'tracking_sync_status',
                    'tracking_sync_attempt_count',
                    'provider_status',
                    'last_tracking_sync_attempt_at',
                    'last_tracking_synced_at',
                    'last_tracking_sync_error',
                ]);
            },
        );
    }
};
