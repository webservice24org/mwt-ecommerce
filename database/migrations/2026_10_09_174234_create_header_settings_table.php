<?php

declare(strict_types=1);

use App\Domain\HeaderBuilder\Enums\HeaderTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('header_settings', function (Blueprint $table): void {
            $table->id();

            /*
             * Header Builder is a global singleton.
             *
             * A unique singleton key prevents multiple
             * competing global header configurations.
             */
            $table
                ->string('singleton_key', 50)
                ->unique();

            /*
             * Stored as a string rather than a database
             * ENUM so new header templates can be added
             * later without another schema migration.
             */
            $table
                ->string('template', 100)
                ->default(
                    HeaderTemplate::MegaMenu->value,
                );

            /*
             * Template configuration lives in JSON.
             *
             * This keeps the database stable when Header
             * Design 2 and Header Design 3 are introduced.
             */
            $table->json('config');

            $table
                ->boolean('is_enabled')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('header_settings');
    }
};
