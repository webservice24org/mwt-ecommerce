<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_search_console_performance_rows', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('seo_performance_sync_run_id');
            $table->unsignedBigInteger('seo_performance_connection_id');
            $table->string('dimension_type', 24);
            $table->string('search_type', 32)->default('web');
            $table->date('data_date');
            $table->text('dimension_value');
            $table->char('dimension_hash', 64);
            $table->decimal('clicks', 18, 4)->default(0);
            $table->decimal('impressions', 18, 4)->default(0);
            $table->decimal('ctr', 12, 10)->default(0);
            $table->decimal('position', 12, 4)->default(0);
            $table->timestamps();

            $table->foreign(
                'seo_performance_sync_run_id',
                'scpr_sync_run_fk',
            )
                ->references('id')
                ->on('seo_performance_sync_runs')
                ->cascadeOnDelete();
            $table->foreign(
                'seo_performance_connection_id',
                'scpr_connection_fk',
            )
                ->references('id')
                ->on('seo_performance_connections')
                ->cascadeOnDelete();
            $table->unique(
                [
                    'seo_performance_sync_run_id',
                    'dimension_type',
                    'data_date',
                    'dimension_hash',
                ],
                'scpr_run_dim_date_hash_uidx',
            );
            $table->index(
                [
                    'seo_performance_connection_id',
                    'dimension_type',
                    'data_date',
                ],
                'scpr_conn_dim_date_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_search_console_performance_rows');
    }
};
