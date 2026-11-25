<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_slug_redirects', function (Blueprint $table): void {
            $table->id();
            $table->string('entity_type', 32);
            $table->unsignedBigInteger('entity_id');
            $table->string('old_slug');
            $table->string('new_slug');
            $table->timestamps();

            $table->unique(
                ['entity_type', 'old_slug'],
                'ssr_type_old_unique',
            );

            $table->index(
                ['entity_type', 'entity_id'],
                'ssr_type_entity_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_slug_redirects');
    }
};
