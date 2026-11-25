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
            'ai_seo_analyses',
            function (Blueprint $table): void {
                $table->id();

                $table->string(
                    'entity_type',
                    40,
                );

                $table->unsignedBigInteger(
                    'entity_id',
                );

                $table->string(
                    'analysis_version',
                    80,
                );

                $table->string(
                    'snapshot_schema_version',
                    80,
                );

                $table->char(
                    'snapshot_hash',
                    64,
                );

                $table->char(
                    'analysis_input_hash',
                    64,
                );

                /*
                 * Immutable audit inputs.
                 */
                $table->json(
                    'source_snapshot',
                );

                $table->json(
                    'resolved_seo',
                );

                /*
                 * Immutable deterministic output.
                 */
                $table->unsignedTinyInteger(
                    'score',
                );

                $table->string(
                    'grade',
                    30,
                );

                $table->json(
                    'breakdown',
                );

                $table->json(
                    'rules',
                );

                $table->json(
                    'findings',
                );

                /*
                 * Audit identity only; no FK so historical
                 * analysis survives later admin deletion.
                 */
                $table
                    ->unsignedBigInteger(
                        'analyzed_by_admin_id',
                    )
                    ->nullable();

                /*
                 * DATETIME is intentional here.
                 *
                 * The application supplies both values
                 * explicitly, so DB-side TIMESTAMP defaults
                 * are unnecessary. DATETIME also avoids
                 * legacy MySQL restrictions on multiple
                 * non-null TIMESTAMP columns without defaults.
                 */
                $table->dateTime(
                    'analyzed_at',
                );

                $table->dateTime(
                    'created_at',
                );

                $table->index(
                    [
                        'entity_type',
                        'entity_id',
                        'analyzed_at',
                    ],
                    'ai_seo_analysis_entity_history',
                );

                $table->index(
                    'snapshot_hash',
                    'ai_seo_analysis_snapshot_hash',
                );

                $table->index(
                    'analysis_input_hash',
                    'ai_seo_analysis_input_hash',
                );

                $table->index(
                    'score',
                    'ai_seo_analysis_score',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_seo_analyses',
        );
    }
};
