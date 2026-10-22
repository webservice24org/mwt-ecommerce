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
            'invoice_settings',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string('company_name')
                    ->default('MWT Ecommerce');

                $table
                    ->string('company_tagline')
                    ->default('Enterprise Store');

                $table
                    ->string('logo_path')
                    ->nullable();

                $table
                    ->string('header_start_color', 7)
                    ->default('#4338ca');

                $table
                    ->string('header_end_color', 7)
                    ->default('#2563eb');

                $table
                    ->string('footer_color', 7)
                    ->default('#0f172a');

                $table
                    ->string('support_email', 254)
                    ->nullable();

                $table
                    ->string('footer_credit', 500)
                    ->default('Thank you for your business!');

                $table->timestamps();
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'invoice_settings',
        );
    }
};
