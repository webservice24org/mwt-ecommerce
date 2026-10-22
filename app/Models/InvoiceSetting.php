<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class InvoiceSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'company_tagline',
        'logo_path',
        'header_start_color',
        'header_end_color',
        'footer_color',
        'support_email',
        'footer_credit',
    ];

    public static function current(): self
    {
        return self::query()
            ->firstOrCreate(
                [],
                [
                    'company_name' => 'MWT Ecommerce',

                    'company_tagline' => 'Enterprise Store',

                    'header_start_color' => '#4338ca',

                    'header_end_color' => '#2563eb',

                    'footer_color' => '#0f172a',

                    'footer_credit' => 'Thank you for your business!',
                ],
            );
    }
}
