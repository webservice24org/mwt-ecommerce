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
            'ai_seo_bulk_apply_batches',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->unsignedBigInteger(
                        'ai_seo_bulk_job_id',
                    );

                $table
                    ->foreign(
                        'ai_seo_bulk_job_id',
                        'ai_seo_bab_job_fk',
                    )
                    ->references('id')
                    ->on('ai_seo_bulk_jobs')
                    ->cascadeOnDelete();

                $table->string(
                    'status',
                    40,
                );

                $table->json(
                    'summary',
                );

                /*
                 * Audit identity only. No FK so history survives
                 * later administrator deletion.
                 */
                $table
                    ->unsignedBigInteger(
                        'applied_by_admin_id',
                    )
                    ->nullable();

                $table
                    ->text('note')
                    ->nullable();

                $table->dateTime(
                    'applied_at',
                );

                $table->dateTime(
                    'created_at',
                );

                $table->index(
                    [
                        'ai_seo_bulk_job_id',
                        'applied_at',
                    ],
                    'ai_seo_bab_job_time',
                );

                $table->index(
                    'status',
                    'ai_seo_bab_status',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_seo_bulk_apply_batches',
        );
    }
};
