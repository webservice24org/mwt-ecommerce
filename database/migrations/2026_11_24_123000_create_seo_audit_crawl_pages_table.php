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
            'seo_audit_crawl_pages',
            function (Blueprint $table): void {
                $table->id();
                $table
                    ->foreignId('seo_audit_run_id')
                    ->constrained('seo_audit_runs')
                    ->cascadeOnDelete();
                $table->char('url_hash', 64);
                $table->text('url');
                $table->string('state', 20);
                $table->unsignedSmallInteger('depth')->default(0);
                $table->string('subject_type', 32);
                $table->string('subject_key')->nullable();
                $table->unsignedSmallInteger('http_status')->nullable();
                $table->string('content_type', 100)->nullable();
                $table->text('redirect_url')->nullable();
                $table->text('canonical_url')->nullable();
                $table->boolean('is_indexable')->nullable();
                $table->text('title')->nullable();
                $table->text('meta_description')->nullable();
                $table->unsignedSmallInteger('h1_count')->nullable();
                $table->unsignedInteger('word_count')->nullable();
                $table->string('fetch_error_code', 100)->nullable();
                $table->text('fetch_error_message')->nullable();
                $table->timestamp('fetched_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['seo_audit_run_id', 'url_hash'],
                    'sacp_run_url_uidx',
                );

                $table->index(
                    ['seo_audit_run_id', 'state'],
                    'sacp_run_state_idx',
                );

                $table->index(
                    ['seo_audit_run_id', 'http_status'],
                    'sacp_run_status_idx',
                );

                $table->index(
                    [
                        'seo_audit_run_id',
                        'subject_type',
                    ],
                    'sacp_run_subject_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'seo_audit_crawl_pages',
        );
    }
};
