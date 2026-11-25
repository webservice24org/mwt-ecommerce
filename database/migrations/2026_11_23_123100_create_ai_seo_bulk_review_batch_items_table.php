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
            'ai_seo_bulk_review_batch_items',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * Use an explicit short FK name.
                 *
                 * Laravel's default generated name:
                 * ai_seo_bulk_review_batch_items_ai_seo_bulk_review_batch_id_foreign
                 *
                 * exceeds MySQL's 64-character identifier limit.
                 */
                $table
                    ->unsignedBigInteger(
                        'ai_seo_bulk_review_batch_id',
                    );

                $table
                    ->foreign(
                        'ai_seo_bulk_review_batch_id',
                        'ai_seo_brbi_batch_fk',
                    )
                    ->references('id')
                    ->on(
                        'ai_seo_bulk_review_batches',
                    )
                    ->cascadeOnDelete();

                /*
                 * Traceability snapshots only. Deliberately no
                 * FK so audit history survives retention changes.
                 */
                $table
                    ->unsignedBigInteger(
                        'ai_seo_bulk_job_item_id',
                    );

                $table
                    ->unsignedBigInteger(
                        'ai_seo_suggestion_id',
                    );

                $table
                    ->unsignedBigInteger(
                        'ai_seo_request_id',
                    );

                $table->string(
                    'field',
                    60,
                );

                $table->string(
                    'result',
                    40,
                );

                $table->string(
                    'previous_decision',
                    20,
                )
                    ->nullable();

                $table->string(
                    'resulting_decision',
                    20,
                )
                    ->nullable();

                $table->string(
                    'freshness_state',
                    40,
                );

                $table->string(
                    'error_code',
                    120,
                )
                    ->nullable();

                $table->text(
                    'error_message',
                )
                    ->nullable();

                $table->dateTime(
                    'created_at',
                );

                $table->unique(
                    [
                        'ai_seo_bulk_review_batch_id',
                        'ai_seo_suggestion_id',
                    ],
                    'ai_seo_bulk_review_batch_suggestion_unique',
                );

                $table->index(
                    [
                        'ai_seo_bulk_job_item_id',
                        'result',
                    ],
                    'ai_seo_bulk_review_item_result',
                );

                $table->index(
                    'ai_seo_suggestion_id',
                    'ai_seo_bulk_review_suggestion',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_seo_bulk_review_batch_items',
        );
    }
};
