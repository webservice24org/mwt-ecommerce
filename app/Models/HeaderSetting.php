<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\HeaderBuilder\Enums\HeaderTemplate;
use App\Domain\HeaderBuilder\HeaderConfigDefaults;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $singleton_key
 * @property HeaderTemplate $template
 * @property array<string, mixed> $config
 * @property bool $is_enabled
 */
final class HeaderSetting extends Model
{
    public const SINGLETON_KEY = 'global';

    protected $fillable = [
        'singleton_key',
        'template',
        'config',
        'is_enabled',
    ];

    public static function singleton(): self
    {
        return self::query()->firstOrCreate(
            [
                'singleton_key' => self::SINGLETON_KEY,
            ],
            [
                'template' => HeaderTemplate::MegaMenu,

                'config' => (
                    new HeaderConfigDefaults
                )->for(
                    HeaderTemplate::MegaMenu,
                ),

                'is_enabled' => true,
            ],
        );
    }

    /**
     * Return the stored config when present,
     * otherwise return the selected template's
     * complete default configuration.
     *
     * @return array<string, mixed>
     */
    public function resolvedConfig(): array
    {
        if ($this->config !== []) {
            return $this->config;
        }

        return (
            new HeaderConfigDefaults
        )->for(
            $this->template,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'template' => HeaderTemplate::class,

            'config' => 'array',

            'is_enabled' => 'boolean',
        ];
    }
}
