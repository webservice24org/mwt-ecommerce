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
            'ai_seo_bulk_job_items',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('ai_seo_bulk_job_id')
                    ->constrained('ai_seo_bulk_jobs')
                    ->cascadeOnDelete();

                $table->unsignedInteger('position');
                $table->string('entity_type', 30);
                $table->unsignedBigInteger('entity_id');
                $table->string('entity_name');
                $table->string('entity_slug');
                $table->char('snapshot_hash', 64);
                $table->unsignedBigInteger('latest_request_id')->nullable();
                $table->char('latest_request_snapshot_hash', 64)->nullable();
                $table->string('state', 30);
                $table->string('planned_action', 30);
                $table->string('reason', 255);
                $table->dateTime('created_at');

                $table->unique(
                    [
                        'ai_seo_bulk_job_id',
                        'entity_type',
                        'entity_id',
                    ],
                    'ai_seo_bulk_job_entity_unique',
                );

                $table->index(
                    ['entity_type', 'entity_id'],
                    'ai_seo_bulk_item_entity',
                );

                $table->index(
                    ['state', 'planned_action'],
                    'ai_seo_bulk_item_state_action',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_seo_bulk_job_items');
    }
};
