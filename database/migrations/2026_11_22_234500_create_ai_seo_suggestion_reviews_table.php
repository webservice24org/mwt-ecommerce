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
            'ai_seo_suggestion_reviews',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId(
                        'ai_seo_suggestion_id',
                    )
                    ->constrained(
                        'ai_seo_suggestions',
                    )
                    ->cascadeOnDelete();

                /*
                 * Append-only review decision.
                 *
                 * "Pending" is represented by the absence
                 * of a review row. Reversing a decision
                 * appends a new review row instead of
                 * overwriting history.
                 */
                $table->string(
                    'decision',
                    30,
                );

                /*
                 * Audit identity only. Deliberately no FK:
                 * review history must survive later admin
                 * deletion.
                 */
                $table
                    ->unsignedBigInteger(
                        'reviewed_by_admin_id',
                    )
                    ->nullable();

                $table
                    ->text(
                        'note',
                    )
                    ->nullable();

                /*
                 * Snapshot audit values captured when the
                 * review decision was made.
                 */
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
                    'freshness_state',
                    40,
                );

                /*
                 * DATETIME is intentional for compatibility
                 * with the local MySQL configuration used
                 * by I.2/I.3.
                 */
                $table->dateTime(
                    'reviewed_at',
                );

                $table->dateTime(
                    'created_at',
                );

                $table->index(
                    [
                        'ai_seo_suggestion_id',
                        'id',
                    ],
                    'ai_seo_suggestion_review_history',
                );

                $table->index(
                    [
                        'decision',
                        'reviewed_at',
                    ],
                    'ai_seo_suggestion_review_decision',
                );

                $table->index(
                    'reviewed_by_admin_id',
                    'ai_seo_suggestion_review_actor',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_seo_suggestion_reviews',
        );
    }
};
