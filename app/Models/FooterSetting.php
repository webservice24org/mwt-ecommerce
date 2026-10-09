<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\FooterConfigDefaults;
use Illuminate\Database\Eloquent\Model;

final class FooterSetting extends Model
{
    public const SINGLETON_KEY =
        'default';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'singleton_key',
        'template',
        'config',
        'is_enabled',
    ];

    public static function singleton(): self
    {
        $template =
            FooterTemplate::LuxeNewsletter;

        return self::query()->firstOrCreate(
            [
                'singleton_key' => self::SINGLETON_KEY,
            ],
            [
                'template' => $template,

                'config' => (
                        new FooterConfigDefaults
                    )->for(
                        $template,
                    ),

                'is_enabled' => true,
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'template' => FooterTemplate::class,

            'config' => 'array',

            'is_enabled' => 'boolean',
        ];
    }
}
