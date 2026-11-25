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
            'seo_meta',
            function (Blueprint $table): void {
                $table->id();

                $table->string(
                    'seoable_type',
                    255,
                );

                $table->unsignedBigInteger(
                    'seoable_id',
                );

                /*
                 * Search metadata overrides.
                 */
                $table
                    ->string(
                        'title',
                        255,
                    )
                    ->nullable();

                $table
                    ->text(
                        'description',
                    )
                    ->nullable();

                /*
                 * Optional canonical override.
                 * Null means resolve from the entity/default rules.
                 */
                $table
                    ->string(
                        'canonical_url',
                        2048,
                    )
                    ->nullable();

                /*
                 * Nullable is intentional:
                 * null = inherit global/entity-default behavior.
                 */
                $table
                    ->boolean(
                        'robots_index',
                    )
                    ->nullable();

                $table
                    ->boolean(
                        'robots_follow',
                    )
                    ->nullable();

                /*
                 * Open Graph overrides.
                 */
                $table
                    ->string(
                        'og_title',
                        255,
                    )
                    ->nullable();

                $table
                    ->text(
                        'og_description',
                    )
                    ->nullable();

                $table
                    ->string(
                        'og_image',
                        2048,
                    )
                    ->nullable();

                /*
                 * Twitter/X overrides.
                 */
                $table
                    ->string(
                        'twitter_title',
                        255,
                    )
                    ->nullable();

                $table
                    ->text(
                        'twitter_description',
                    )
                    ->nullable();

                $table
                    ->string(
                        'twitter_image',
                        2048,
                    )
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'seoable_type',
                        'seoable_id',
                    ],
                    'seo_meta_seoable_unique',
                );

                $table->index(
                    [
                        'seoable_type',
                        'seoable_id',
                    ],
                    'seo_meta_seoable_index',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'seo_meta',
        );
    }
};
