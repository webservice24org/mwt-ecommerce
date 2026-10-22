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
            'mail_settings',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string(
                        'mailer',
                        32,
                    )
                    ->default(
                        'smtp',
                    );

                $table
                    ->string(
                        'host',
                    )
                    ->nullable();

                $table
                    ->unsignedSmallInteger(
                        'port',
                    )
                    ->nullable();

                $table
                    ->string(
                        'username',
                    )
                    ->nullable();

                /*
                 * Stored encrypted by the model.
                 *
                 * Never return this value to the
                 * browser after persistence.
                 */
                $table
                    ->text(
                        'password',
                    )
                    ->nullable();

                $table
                    ->string(
                        'encryption',
                        16,
                    )
                    ->nullable();

                $table
                    ->string(
                        'from_address',
                        254,
                    )
                    ->nullable();

                $table
                    ->string(
                        'from_name',
                    )
                    ->nullable();

                $table
                    ->string(
                        'admin_order_notification_email',
                        254,
                    )
                    ->nullable();

                $table
                    ->boolean(
                        'enabled',
                    )
                    ->default(
                        false,
                    );

                $table->timestamps();
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'mail_settings',
        );
    }
};
