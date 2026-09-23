<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->string('content_mode', 20)
                ->default('classic')
                ->after('status');

            $table->longText('content')
                ->nullable()
                ->after('content_mode');

            $table->string('featured_image')
                ->nullable()
                ->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn([
                'content_mode',
                'content',
                'featured_image',
            ]);
        });
    }
};
