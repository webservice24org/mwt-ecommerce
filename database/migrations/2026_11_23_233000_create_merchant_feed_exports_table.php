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
            'merchant_feed_exports',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('public_id')->unique('mfe_public_uidx');

                $table
                    ->foreignId('requested_by_admin_id')
                    ->nullable()
                    ->constrained('admins')
                    ->nullOnDelete();

                $table->string('requested_by_name');
                $table->string('requested_by_email');

                $table->string('status', 20);
                $table->string('format', 16);
                $table->string('schema_version', 64);
                $table->string('readiness_policy_version', 64);
                $table->unsignedInteger('merchant_policy_version');

                $table->char('snapshot_hash', 64);
                $table->json('snapshot');

                $table->unsignedInteger('products_considered');
                $table->unsignedInteger('item_candidates');
                $table->unsignedInteger('exportable_count');
                $table->unsignedInteger('excluded_count');
                $table->unsignedInteger('out_of_stock_count');
                $table->unsignedInteger('blocker_count');
                $table->unsignedInteger('warning_count');
                $table->unsignedInteger('info_count');
                $table->boolean('truncated')->default(false);
                $table->unsignedInteger('max_items');
                $table->unsignedSmallInteger('attempt_count')->default(0);

                $table->string('file_disk', 64)->nullable();
                $table->string('file_path')->nullable();
                $table->string('file_name')->nullable();
                $table->string('file_mime_type', 120)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->char('file_sha256', 64)->nullable();

                $table->string('failure_code', 100)->nullable();
                $table->text('failure_message')->nullable();

                $table->timestamp('queued_at');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamps();

                $table->index(
                    ['status', 'created_at'],
                    'mfe_status_created_idx',
                );

                $table->index(
                    ['requested_by_admin_id', 'created_at'],
                    'mfe_admin_created_idx',
                );

                $table->index(
                    'snapshot_hash',
                    'mfe_snapshot_hash_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'merchant_feed_exports',
        );
    }
};
