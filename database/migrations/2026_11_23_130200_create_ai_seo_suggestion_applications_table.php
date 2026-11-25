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
            'ai_seo_suggestion_applications',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * Unique suggestion application ledger.
                 * No FK: application audit must survive later
                 * request/suggestion retention policy changes.
                 */
                $table
                    ->unsignedBigInteger(
                        'ai_seo_suggestion_id',
                    )
                    ->unique(
                        'ai_seo_sa_suggestion_unique',
                    );

                $table
                    ->unsignedBigInteger(
                        'ai_seo_suggestion_review_id',
                    );

                $table
                    ->unsignedBigInteger(
                        'ai_seo_request_id',
                    );

                $table
                    ->unsignedBigInteger(
                        'ai_seo_bulk_job_id',
                    );

                $table
                    ->unsignedBigInteger(
                        'ai_seo_bulk_job_item_id',
                    );

                $table->string(
                    'entity_type',
                    30,
                );

                $table
                    ->unsignedBigInteger(
                        'entity_id',
                    );

                $table->string(
                    'field',
                    60,
                );

                $table->text(
                    'baseline_value',
                )
                    ->nullable();

                $table->text(
                    'before_value',
                )
                    ->nullable();

                $table->text(
                    'applied_value',
                );

                $table
                    ->char(
                        'source_snapshot_hash',
                        64,
                    );

                $table
                    ->char(
                        'current_snapshot_hash',
                        64,
                    );

                $table
                    ->unsignedBigInteger(
                        'applied_by_admin_id',
                    )
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
                    'ai_seo_sa_job_time',
                );

                $table->index(
                    [
                        'entity_type',
                        'entity_id',
                    ],
                    'ai_seo_sa_entity',
                );

                $table->index(
                    'ai_seo_bulk_job_item_id',
                    'ai_seo_sa_item',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_seo_suggestion_applications',
        );
    }
};
