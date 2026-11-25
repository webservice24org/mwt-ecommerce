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
            'seo_audit_internal_links',
            function (Blueprint $table): void {
                $table->id();
                $table
                    ->foreignId('seo_audit_run_id')
                    ->constrained('seo_audit_runs')
                    ->cascadeOnDelete();
                $table
                    ->foreignId('source_page_id')
                    ->constrained(
                        'seo_audit_crawl_pages',
                    )
                    ->cascadeOnDelete();
                $table
                    ->foreignId('target_page_id')
                    ->constrained(
                        'seo_audit_crawl_pages',
                    )
                    ->cascadeOnDelete();
                $table->char('edge_hash', 64);
                $table->char('target_url_hash', 64);
                $table->text('target_url');
                $table->string('anchor_text', 500)->nullable();
                $table->boolean('nofollow')->default(false);
                $table->unsignedSmallInteger('occurrences')->default(1);
                $table->timestamps();

                $table->unique(
                    [
                        'seo_audit_run_id',
                        'edge_hash',
                    ],
                    'sail_run_edge_uidx',
                );

                $table->index(
                    [
                        'seo_audit_run_id',
                        'target_url_hash',
                    ],
                    'sail_run_target_idx',
                );

                $table->index(
                    ['source_page_id'],
                    'sail_source_idx',
                );

                $table->index(
                    ['target_page_id'],
                    'sail_target_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'seo_audit_internal_links',
        );
    }
};
