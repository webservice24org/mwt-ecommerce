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
            'merchant_product_data_events',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId(
                        'merchant_product_data_id',
                    )
                    ->constrained(
                        'merchant_product_data',
                    )
                    ->cascadeOnDelete();

                $table
                    ->unsignedInteger(
                        'version',
                    );

                $table
                    ->json(
                        'before_data',
                    )
                    ->nullable();

                $table->json(
                    'after_data',
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
                        'merchant_product_data_id',
                        'version',
                    ],
                    'merchant_product_data_event_ver_uq',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'merchant_product_data_events',
        );
    }
};
