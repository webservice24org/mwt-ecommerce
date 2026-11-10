<?php

declare(strict_types=1);

use App\Domain\Shipping\Enums\ShipmentProviderReconciliationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'shipment_provider_submissions';

    private const MAPPED_STATUS_INDEX = 'sps_provider_mapped_status_idx';

    private const RECONCILIATION_STATUS_INDEX = 'sps_reconciliation_status_idx';

    public function up(): void
    {
        if (! Schema::hasColumn(self::TABLE, 'provider_mapped_shipment_status')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->string('provider_mapped_shipment_status', 32)
                    ->nullable()
                    ->after('provider_status');
            });
        }

        if (! Schema::hasColumn(self::TABLE, 'reconciliation_status')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->string('reconciliation_status', 32)
                    ->default(ShipmentProviderReconciliationStatus::Pending->value)
                    ->after('provider_mapped_shipment_status');
            });
        }

        if (! Schema::hasColumn(self::TABLE, 'reconciliation_attempt_count')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->unsignedInteger('reconciliation_attempt_count')
                    ->default(0)
                    ->after('reconciliation_status');
            });
        }

        if (! Schema::hasColumn(self::TABLE, 'last_reconciliation_attempt_at')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->timestamp('last_reconciliation_attempt_at')
                    ->nullable()
                    ->after('reconciliation_attempt_count');
            });
        }

        if (! Schema::hasColumn(self::TABLE, 'last_reconciled_at')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->timestamp('last_reconciled_at')
                    ->nullable()
                    ->after('last_reconciliation_attempt_at');
            });
        }

        if (! Schema::hasColumn(self::TABLE, 'last_reconciliation_error')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->string('last_reconciliation_error', 255)
                    ->nullable()
                    ->after('last_reconciled_at');
            });
        }

        if (! Schema::hasIndex(self::TABLE, self::MAPPED_STATUS_INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->index(
                    'provider_mapped_shipment_status',
                    self::MAPPED_STATUS_INDEX,
                );
            });
        }

        if (! Schema::hasIndex(self::TABLE, self::RECONCILIATION_STATUS_INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->index(
                    'reconciliation_status',
                    self::RECONCILIATION_STATUS_INDEX,
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex(self::TABLE, self::MAPPED_STATUS_INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->dropIndex(self::MAPPED_STATUS_INDEX);
            });
        }

        if (Schema::hasIndex(self::TABLE, self::RECONCILIATION_STATUS_INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->dropIndex(self::RECONCILIATION_STATUS_INDEX);
            });
        }

        foreach ([
            'provider_mapped_shipment_status',
            'reconciliation_status',
            'reconciliation_attempt_count',
            'last_reconciliation_attempt_at',
            'last_reconciled_at',
            'last_reconciliation_error',
        ] as $column) {
            if (! Schema::hasColumn(self::TABLE, $column)) {
                continue;
            }

            Schema::table(self::TABLE, static function (Blueprint $table) use ($column): void {
                $table->dropColumn($column);
            });
        }
    }
};
