<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\WebsiteSettings\ProductCardStyleSchema;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $section
 * @property array<string, mixed> $config
 */
final class ThemeSetting extends Model
{
    public const PRODUCT_CARDS = 'product_cards';

    protected $primaryKey = 'section';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['section', 'config'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['config' => 'array'];
    }

    /**
     * @return array<string, string|int>
     */
    public static function productCardStyles(): array
    {
        $setting = self::query()->find(self::PRODUCT_CARDS);
        $errors = [];

        return (new ProductCardStyleSchema)->normalize($setting->config ?? [], $errors);
    }
}
