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
            'ai_seo_bulk_apply_batch_items',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->unsignedBigInteger(
                        'ai_seo_bulk_apply_batch_id',
                    );

                $table
                    ->foreign(
                        'ai_seo_bulk_apply_batch_id',
                        'ai_seo_babi_batch_fk',
                    )
                    ->references('id')
                    ->on(
                        'ai_seo_bulk_apply_batches',
                    )
                    ->cascadeOnDelete();

                /*
                 * Historical identifiers intentionally have no
                 * foreign keys. Audit rows should remain readable
                 * if request/suggestion retention changes later.
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
                        'ai_seo_suggestion_review_id',
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'ai_seo_request_id',
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

                $table->string(
                    'result',
                    40,
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
                    'proposed_value',
                );

                $table->text(
                    'after_value',
                )
                    ->nullable();

                $table
                    ->char(
                        'source_snapshot_hash',
                        64,
                    )
                    ->nullable();

                $table
                    ->char(
                        'current_snapshot_hash',
                        64,
                    )
                    ->nullable();

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
                        'ai_seo_bulk_apply_batch_id',
                        'ai_seo_suggestion_id',
                    ],
                    'ai_seo_babi_batch_suggestion_unique',
                );

                $table->index(
                    [
                        'ai_seo_bulk_job_item_id',
                        'result',
                    ],
                    'ai_seo_babi_item_result',
                );

                $table->index(
                    [
                        'entity_type',
                        'entity_id',
                    ],
                    'ai_seo_babi_entity',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_seo_bulk_apply_batch_items',
        );
    }
};
