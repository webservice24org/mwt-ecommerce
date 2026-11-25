<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'merchant_structured_data_policy_events',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->unsignedBigInteger(
                        'merchant_structured_data_policy_id',
                    );

                $table
                    ->foreign(
                        'merchant_structured_data_policy_id',
                        'merchant_sd_event_policy_fk',
                    )
                    ->references('id')
                    ->on(
                        'merchant_structured_data_policies',
                    )
                    ->cascadeOnDelete();

                $table
                    ->unsignedInteger(
                        'version',
                    );

                $table
                    ->json(
                        'before_policy',
                    )
                    ->nullable();

                $table->json(
                    'after_policy',
                );

                $table
                    ->unsignedBigInteger(
                        'changed_by_admin_id',
                    )
                    ->nullable();

                $table->dateTime(
                    'created_at',
                );

                $table->unique(
                    [
                        'merchant_structured_data_policy_id',
                        'version',
                    ],
                    'merchant_sd_event_policy_ver_uq',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'merchant_structured_data_policy_events',
        );
    }
};
