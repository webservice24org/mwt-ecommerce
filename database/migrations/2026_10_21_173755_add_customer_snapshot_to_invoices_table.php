<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'invoices',
            function (Blueprint $table): void {
                $table
                    ->string(
                        'customer_name',
                        255,
                    )
                    ->after(
                        'invoice_number',
                    );

                $table
                    ->string(
                        'customer_email',
                        254,
                    )
                    ->after(
                        'customer_name',
                    );
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'invoices',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'customer_name',
                    'customer_email',
                ]);
            },
        );
    }
};
