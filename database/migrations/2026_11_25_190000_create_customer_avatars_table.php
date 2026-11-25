<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_avatars', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->string('mime_type', 32);
            $table->mediumText('image_base64');
            $table->uuid('version');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_avatars');
    }
};
