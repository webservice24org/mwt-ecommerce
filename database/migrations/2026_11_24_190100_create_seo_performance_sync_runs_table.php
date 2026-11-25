<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_performance_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique('spsr_public_uidx');
            $table->unsignedBigInteger('seo_performance_connection_id');
            $table->string('scope', 40);
            $table->string('mode', 24);
            $table->string('status', 24)->default('queued');
            $table->date('date_from');
            $table->date('date_to');
            $table->unsignedBigInteger('requested_by_admin_id')->nullable();
            $table->string('requested_by_name', 120)->nullable();
            $table->string('requested_by_email', 255)->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->unsignedInteger('rows_received')->default(0);
            $table->unsignedInteger('rows_persisted')->default(0);
            $table->string('failure_code', 80)->nullable();
            $table->string('failure_message', 500)->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->foreign(
                'seo_performance_connection_id',
                'spsr_connection_fk',
            )
                ->references('id')
                ->on('seo_performance_connections')
                ->cascadeOnDelete();
            $table->foreign(
                'requested_by_admin_id',
                'spsr_requested_admin_fk',
            )
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
            $table->index(
                ['seo_performance_connection_id', 'status'],
                'spsr_connection_status_idx',
            );
            $table->index(
                ['scope', 'date_from', 'date_to'],
                'spsr_scope_dates_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_performance_sync_runs');
    }
};
