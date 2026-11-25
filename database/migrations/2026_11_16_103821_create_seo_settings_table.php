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
            'seo_settings',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * Site identity
                 */
                $table
                    ->string(
                        'site_name',
                        190,
                    )
                    ->nullable();

                $table
                    ->string(
                        'title_separator',
                        10,
                    )
                    ->default('|');

                $table
                    ->string(
                        'default_title_template',
                        255,
                    )
                    ->default(
                        '{page_title} {separator} {site_name}',
                    );

                $table
                    ->text(
                        'default_meta_description',
                    )
                    ->nullable();

                /*
                 * Canonical / indexation defaults
                 */
                $table
                    ->string(
                        'canonical_host',
                        255,
                    )
                    ->nullable();

                $table
                    ->boolean(
                        'default_robots_index',
                    )
                    ->default(true);

                $table
                    ->boolean(
                        'default_robots_follow',
                    )
                    ->default(true);

                /*
                 * Social metadata
                 */
                $table
                    ->string(
                        'default_og_image',
                        2048,
                    )
                    ->nullable();

                $table
                    ->string(
                        'twitter_card',
                        50,
                    )
                    ->default(
                        'summary_large_image',
                    );

                /*
                 * Organization / merchant identity
                 */
                $table
                    ->string(
                        'organization_name',
                        190,
                    )
                    ->nullable();

                $table
                    ->string(
                        'organization_logo',
                        2048,
                    )
                    ->nullable();

                $table
                    ->json(
                        'organization_social_profiles',
                    )
                    ->nullable();

                /*
                 * Search engine verification
                 */
                $table
                    ->string(
                        'google_site_verification',
                        255,
                    )
                    ->nullable();

                $table
                    ->string(
                        'bing_site_verification',
                        255,
                    )
                    ->nullable();

                /*
                 * Future feature gates
                 */
                $table
                    ->boolean(
                        'enable_structured_data',
                    )
                    ->default(true);

                $table
                    ->boolean(
                        'enable_open_graph',
                    )
                    ->default(true);

                $table
                    ->boolean(
                        'enable_twitter_cards',
                    )
                    ->default(true);

                $table
                    ->boolean(
                        'enable_sitemap',
                    )
                    ->default(true);

                $table
                    ->timestamps();
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'seo_settings',
        );
    }
};
