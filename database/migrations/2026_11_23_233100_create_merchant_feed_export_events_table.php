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
            'merchant_feed_export_events',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('merchant_feed_export_id')
                    ->constrained('merchant_feed_exports')
                    ->cascadeOnDelete();

                $table->string('event_type', 24);
                $table->string('from_status', 20)->nullable();
                $table->string('to_status', 20);

                $table
                    ->foreignId('actor_admin_id')
                    ->nullable()
                    ->constrained('admins')
                    ->nullOnDelete();

                $table->string('actor_name')->nullable();
                $table->string('actor_email')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('occurred_at');

                $table->index(
                    ['merchant_feed_export_id', 'id'],
                    'mfee_export_event_idx',
                );

                $table->index(
                    'occurred_at',
                    'mfee_occurred_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'merchant_feed_export_events',
        );
    }
};
