<?php

declare(strict_types=1);

use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shipment_provider_submissions',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId(
                    'shipment_id',
                )
                    ->unique()
                    ->constrained(
                        'shipments',
                    )
                    ->cascadeOnDelete();

                $table->string(
                    'provider_code',
                    80,
                )->index();

                $table->uuid(
                    'idempotency_key',
                )->unique();

                $table->string(
                    'status',
                    32,
                )
                    ->default(
                        ShipmentProviderSubmissionStatus::Pending
                            ->value,
                    )
                    ->index();

                $table->string(
                    'external_reference',
                    190,
                )->nullable();

                $table->unsignedInteger(
                    'attempt_count',
                )->default(
                    0,
                );

                $table->timestamp(
                    'last_attempted_at',
                )->nullable();

                $table->timestamp(
                    'submitted_at',
                )->nullable();

                $table->string(
                    'last_error',
                    255,
                )->nullable();

                $table->json(
                    'metadata',
                )->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'provider_code',
                        'external_reference',
                    ],
                    'shipment_provider_external_unique',
                );

                $table->index(
                    [
                        'provider_code',
                        'status',
                    ],
                    'shipment_provider_status_index',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shipment_provider_submissions',
        );
    }
};
