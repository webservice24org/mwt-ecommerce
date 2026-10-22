<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class MailSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'mailer',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
        'admin_order_notification_email',
        'enabled',
    ];

    public static function current(): self
    {
        return self::query()
            ->firstOrCreate(
                [],
                [
                    'mailer' => 'smtp',

                    'enabled' => false,
                ],
            );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            /*
             * Laravel's encrypted cast ensures
             * the SMTP password is never stored
             * as plaintext in the database.
             */
            'password' => 'encrypted',

            'port' => 'integer',

            'enabled' => 'boolean',
        ];
    }
}
