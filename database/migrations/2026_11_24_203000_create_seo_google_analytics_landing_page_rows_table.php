<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_google_analytics_landing_page_rows', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('seo_performance_sync_run_id');
            $table->unsignedBigInteger('seo_performance_connection_id');
            $table->date('data_date');
            $table->text('landing_page');
            $table->char('landing_page_hash', 64);
            $table->decimal('sessions', 18, 4)->default(0);
            $table->decimal('engaged_sessions', 18, 4)->default(0);
            $table->decimal('active_users', 18, 4)->default(0);
            $table->decimal('new_users', 18, 4)->default(0);
            $table->decimal('key_events', 18, 4)->default(0);
            $table->decimal('total_revenue', 20, 6)->default(0);
            $table->timestamps();

            $table->foreign(
                'seo_performance_sync_run_id',
                'galpr_sync_run_fk',
            )
                ->references('id')
                ->on('seo_performance_sync_runs')
                ->cascadeOnDelete();
            $table->foreign(
                'seo_performance_connection_id',
                'galpr_connection_fk',
            )
                ->references('id')
                ->on('seo_performance_connections')
                ->cascadeOnDelete();
            $table->unique(
                [
                    'seo_performance_sync_run_id',
                    'data_date',
                    'landing_page_hash',
                ],
                'galpr_run_date_hash_uidx',
            );
            $table->index(
                ['seo_performance_connection_id', 'data_date'],
                'galpr_conn_date_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_google_analytics_landing_page_rows');
    }
};
