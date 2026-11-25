<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_audit_runs', function (Blueprint $table): void {
            $table->string('orchestration_version', 64)->nullable();
            $table->unsignedSmallInteger('queue_passes')->default(0);
            $table->timestamp('catalog_seeded_at')->nullable();
            $table->timestamp('crawl_settled_at')->nullable();
            $table->timestamp('image_health_enriched_at')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamp('last_progress_at')->nullable();
            $table->index(
                ['status', 'last_progress_at'],
                'sar_status_progress_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('seo_audit_runs', function (Blueprint $table): void {
            $table->dropIndex('sar_status_progress_idx');
            $table->dropColumn([
                'orchestration_version',
                'queue_passes',
                'catalog_seeded_at',
                'crawl_settled_at',
                'image_health_enriched_at',
                'evaluated_at',
                'last_progress_at',
            ]);
        });
    }
};
