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
            'ai_seo_bulk_jobs',
            function (Blueprint $table): void {
                $table->id();
                $table->string('reference', 80)->unique();
                $table->string('status', 40);
                $table->char('plan_hash', 64)->index();
                $table->json('entity_types');
                $table->json('filters');
                $table->json('summary');
                $table->unsignedBigInteger('planned_by_admin_id')->nullable();
                $table->dateTime('planned_at');
                $table->dateTime('created_at');

                $table->index(
                    ['status', 'planned_at'],
                    'ai_seo_bulk_jobs_status_planned',
                );

                $table->index(
                    'planned_by_admin_id',
                    'ai_seo_bulk_jobs_actor',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_seo_bulk_jobs');
    }
};
