<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Concerns\HasSeoMeta;
use App\Domain\Seo\Contracts\Seoable;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $logo_path
 * @property bool $is_active
 * @property int $position
 * @property string|null $meta_title
 * @property string|null $meta_description
 */
class Brand extends Model implements Seoable
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory, HasSeoMeta;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_path',
        'is_active',
        'position',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
