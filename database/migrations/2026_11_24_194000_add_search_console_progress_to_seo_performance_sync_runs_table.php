<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('seo_performance_sync_runs', 'provider_page_token')) {
            Schema::table('seo_performance_sync_runs', function (Blueprint $table): void {
                $table->string('provider_page_token', 191)->nullable()->after('rows_persisted');
            });
        }

        if (! Schema::hasColumn('seo_performance_sync_runs', 'provider_complete')) {
            Schema::table('seo_performance_sync_runs', function (Blueprint $table): void {
                $table->boolean('provider_complete')->default(false)->after('provider_page_token');
            });
        }

        if (! Schema::hasColumn('seo_performance_sync_runs', 'provider_truncated')) {
            Schema::table('seo_performance_sync_runs', function (Blueprint $table): void {
                $table->boolean('provider_truncated')->default(false)->after('provider_complete');
            });
        }

        if (! Schema::hasColumn('seo_performance_sync_runs', 'queue_pass_count')) {
            Schema::table('seo_performance_sync_runs', function (Blueprint $table): void {
                $table->unsignedSmallInteger('queue_pass_count')->default(0)->after('provider_truncated');
            });
        }

        if (! Schema::hasColumn('seo_performance_sync_runs', 'orchestration_version')) {
            Schema::table('seo_performance_sync_runs', function (Blueprint $table): void {
                $table->string('orchestration_version', 64)->nullable()->after('queue_pass_count');
            });
        }

        if (! Schema::hasColumn('seo_performance_sync_runs', 'last_progress_at')) {
            Schema::table('seo_performance_sync_runs', function (Blueprint $table): void {
                $table->timestamp('last_progress_at')->nullable()->after('orchestration_version');
            });
        }

        if (! Schema::hasIndex('seo_performance_sync_runs', 'spsr_status_provider_done_idx')) {
            Schema::table('seo_performance_sync_runs', function (Blueprint $table): void {
                $table->index(
                    ['status', 'provider_complete'],
                    'spsr_status_provider_done_idx',
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('seo_performance_sync_runs', 'spsr_status_provider_done_idx')) {
            Schema::table('seo_performance_sync_runs', function (Blueprint $table): void {
                $table->dropIndex('spsr_status_provider_done_idx');
            });
        }

        $columns = [
            'provider_page_token',
            'provider_complete',
            'provider_truncated',
            'queue_pass_count',
            'orchestration_version',
            'last_progress_at',
        ];

        $existing = array_values(array_filter(
            $columns,
            static fn (string $column): bool => Schema::hasColumn('seo_performance_sync_runs', $column),
        ));

        if ($existing !== []) {
            Schema::table('seo_performance_sync_runs', function (Blueprint $table) use ($existing): void {
                $table->dropColumn($existing);
            });
        }
    }
};
