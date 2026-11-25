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
            'ai_seo_analysis_enrichments',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId(
                        'ai_seo_analysis_id',
                    )
                    ->constrained(
                        'ai_seo_analyses',
                    )
                    ->cascadeOnDelete();

                /*
                 * Copy the immutable deterministic-analysis identity
                 * into the enrichment row so later auditing does not
                 * depend on re-computing anything.
                 */
                $table->char(
                    'analysis_input_hash',
                    64,
                );

                $table->string(
                    'prompt_version',
                    80,
                );

                $table->string(
                    'status',
                    30,
                );

                $table->string(
                    'provider',
                    50,
                );

                $table->string(
                    'model_name',
                    120,
                );

                $table->char(
                    'input_hash',
                    64,
                );

                /*
                 * Exact provider-safe context that was generated from
                 * the immutable analysis. No credentials are included.
                 */
                $table->json(
                    'input_context',
                );

                /*
                 * Only application-validated enrichment output.
                 */
                $table
                    ->text(
                        'summary',
                    )
                    ->nullable();

                $table
                    ->json(
                        'actions',
                    )
                    ->nullable();

                /*
                 * Provider response ID only. Raw response bodies are
                 * deliberately not persisted by I.3.
                 */
                $table
                    ->string(
                        'provider_response_id',
                        255,
                    )
                    ->nullable();

                $table
                    ->string(
                        'error_code',
                        100,
                    )
                    ->nullable();

                $table
                    ->string(
                        'error_message',
                        1000,
                    )
                    ->nullable();

                /*
                 * Audit identity only; no FK so enrichment history
                 * survives later admin deletion.
                 */
                $table
                    ->unsignedBigInteger(
                        'enriched_by_admin_id',
                    )
                    ->nullable();

                /*
                 * DATETIME is intentional for compatibility with the
                 * project's MySQL configuration. Laravel supplies all
                 * lifecycle values explicitly.
                 */
                $table->dateTime(
                    'started_at',
                );

                $table
                    ->dateTime(
                        'completed_at',
                    )
                    ->nullable();

                $table->dateTime(
                    'created_at',
                );

                $table->index(
                    [
                        'ai_seo_analysis_id',
                        'created_at',
                    ],
                    'ai_seo_analysis_enrichment_history',
                );

                $table->index(
                    [
                        'status',
                        'created_at',
                    ],
                    'ai_seo_analysis_enrichment_status',
                );

                $table->index(
                    'input_hash',
                    'ai_seo_analysis_enrichment_input_hash',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_seo_analysis_enrichments',
        );
    }
};
