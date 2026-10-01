<?php

declare(strict_types=1);

use App\Domain\PageBuilder\Enums\PageLayout;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'pages',
            function (Blueprint $table): void {
                $table
                    ->string('layout', 50)
                    ->default(
                        PageLayout::FullWidth->value,
                    )
                    ->after('type');
            },
        );

        Schema::table(
            'page_sections',
            function (Blueprint $table): void {
                $table
                    ->json('layout')
                    ->nullable()
                    ->after('config');
            },
        );

        /*
         * We intentionally keep the section layout
         * column nullable at the database level.
         *
         * Existing rows therefore remain valid.
         * Application-level normalization will treat
         * a missing/null layout as:
         *
         *     width = container
         *
         * We will formalize that contract in 4.13C.
         */
    }

    public function down(): void
    {
        Schema::table(
            'page_sections',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'layout',
                );
            },
        );

        Schema::table(
            'pages',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'layout',
                );
            },
        );
    }
};