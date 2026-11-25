<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'ai_seo_bulk_jobs',
            function (Blueprint $table): void {
                $table
                    ->json('execution_summary')
                    ->nullable()
                    ->after('summary');

                $table
                    ->dateTime('started_at')
                    ->nullable()
                    ->after('planned_at');

                $table
                    ->dateTime('last_dispatched_at')
                    ->nullable()
                    ->after('started_at');

                $table
                    ->dateTime('completed_at')
                    ->nullable()
                    ->after('last_dispatched_at');
            },
        );

        Schema::table(
            'ai_seo_bulk_job_items',
            function (Blueprint $table): void {
                $table
                    ->string(
                        'execution_status',
                        30,
                    )
                    ->default('planned')
                    ->after('planned_action');

                $table
                    ->unsignedInteger('attempt_count')
                    ->default(0)
                    ->after('execution_status');

                /*
                 * Latest H.1 request used by this bulk item.
                 * No FK: request-retention policy must not
                 * erase historical bulk execution records.
                 */
                $table
                    ->unsignedBigInteger('ai_seo_request_id')
                    ->nullable()
                    ->after('attempt_count');

                $table
                    ->string(
                        'execution_error_code',
                        120,
                    )
                    ->nullable()
                    ->after('reason');

                $table
                    ->text('execution_error_message')
                    ->nullable()
                    ->after('execution_error_code');

                $table
                    ->dateTime('queued_at')
                    ->nullable()
                    ->after('execution_error_message');

                $table
                    ->dateTime('started_at')
                    ->nullable()
                    ->after('queued_at');

                $table
                    ->dateTime('last_attempt_at')
                    ->nullable()
                    ->after('started_at');

                $table
                    ->dateTime('completed_at')
                    ->nullable()
                    ->after('last_attempt_at');

                $table->index(
                    [
                        'ai_seo_bulk_job_id',
                        'execution_status',
                    ],
                    'ai_seo_bulk_item_execution_status',
                );

                $table->index(
                    'ai_seo_request_id',
                    'ai_seo_bulk_item_request',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'ai_seo_bulk_job_items',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'ai_seo_bulk_item_execution_status',
                );

                $table->dropIndex(
                    'ai_seo_bulk_item_request',
                );

                $table->dropColumn([
                    'execution_status',
                    'attempt_count',
                    'ai_seo_request_id',
                    'execution_error_code',
                    'execution_error_message',
                    'queued_at',
                    'started_at',
                    'last_attempt_at',
                    'completed_at',
                ]);
            },
        );

        Schema::table(
            'ai_seo_bulk_jobs',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'execution_summary',
                    'started_at',
                    'last_dispatched_at',
                    'completed_at',
                ]);
            },
        );
    }
};
