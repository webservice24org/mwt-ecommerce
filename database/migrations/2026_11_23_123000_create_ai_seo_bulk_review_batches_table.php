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
            'ai_seo_bulk_review_batches',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('ai_seo_bulk_job_id')
                    ->constrained('ai_seo_bulk_jobs')
                    ->cascadeOnDelete();

                $table->string('decision', 20);
                $table->string('status', 40);
                $table->json('summary');
                $table->unsignedBigInteger('reviewed_by_admin_id')->nullable();
                $table->text('note')->nullable();
                $table->dateTime('reviewed_at');
                $table->dateTime('created_at');

                $table->index(
                    ['ai_seo_bulk_job_id', 'reviewed_at'],
                    'ai_seo_bulk_review_job_time',
                );

                $table->index(
                    ['decision', 'status'],
                    'ai_seo_bulk_review_decision_status',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_seo_bulk_review_batches');
    }
};
