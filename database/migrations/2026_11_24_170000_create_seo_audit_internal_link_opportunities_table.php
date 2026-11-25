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
            'seo_audit_internal_link_opportunities',
            function (Blueprint $table): void {
                $table->id();
                $table
                    ->foreignId('seo_audit_run_id')
                    ->constrained('seo_audit_runs')
                    ->cascadeOnDelete();
                $table
                    ->foreignId('source_page_id')
                    ->constrained('seo_audit_crawl_pages')
                    ->cascadeOnDelete();
                $table
                    ->foreignId('target_page_id')
                    ->constrained('seo_audit_crawl_pages')
                    ->cascadeOnDelete();
                $table->char('fingerprint', 64);
                $table->string('engine_version', 64);
                $table->string('review_state', 20)->default('pending');
                $table->unsignedSmallInteger('score');
                $table->string('target_subject_type', 32);
                $table->string('target_subject_key')->nullable();
                $table->text('source_url');
                $table->text('target_url');
                $table->string('suggested_anchor', 255)->nullable();
                $table->text('reason');
                $table->timestamps();

                $table->unique(
                    ['seo_audit_run_id', 'fingerprint'],
                    'sailo_run_fingerprint_uidx',
                );
                $table->index(
                    ['seo_audit_run_id', 'review_state'],
                    'sailo_run_state_idx',
                );
                $table->index(
                    ['seo_audit_run_id', 'target_page_id'],
                    'sailo_run_target_idx',
                );
                $table->index(
                    ['seo_audit_run_id', 'score'],
                    'sailo_run_score_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'seo_audit_internal_link_opportunities',
        );
    }
};
