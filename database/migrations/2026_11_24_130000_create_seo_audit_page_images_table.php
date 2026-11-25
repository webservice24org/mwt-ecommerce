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
            'seo_audit_page_images',
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
                $table->char('observation_hash', 64);
                $table->char('image_url_hash', 64);
                $table->text('image_url');
                $table
                    ->boolean('alt_attribute_present')
                    ->default(false);
                $table->text('alt_text')->nullable();
                $table
                    ->unsignedSmallInteger('occurrences')
                    ->default(1);
                $table->timestamps();

                $table->unique(
                    [
                        'seo_audit_run_id',
                        'source_page_id',
                        'observation_hash',
                    ],
                    'sapi_run_page_obs_uidx',
                );

                $table->index(
                    [
                        'seo_audit_run_id',
                        'image_url_hash',
                    ],
                    'sapi_run_image_idx',
                );

                $table->index(
                    [
                        'seo_audit_run_id',
                        'alt_attribute_present',
                    ],
                    'sapi_run_alt_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'seo_audit_page_images',
        );
    }
};
