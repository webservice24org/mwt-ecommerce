<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $brand_id
 * @property string $name
 * @property string $slug
 * @property string|null $short_description
 * @property string|null $description
 * @property ProductStatus $status
 * @property bool $is_featured
 * @property int $position
 * @property Carbon|null $published_at
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property ProductType $type
 * @property string|null $sku
 * @property int|null $price
 * @property int|null $compare_at_price
 * @property int|null $cost_price
 */
final class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'type',
        'sku',
        'price',
        'compare_at_price',
        'cost_price',
        'name',
        'slug',
        'short_description',
        'description',
        'status',
        'is_featured',
        'position',
        'published_at',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'status' => ProductStatus::class,
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'cost_price' => 'integer',
            'is_featured' => 'boolean',
            'position' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withTimestamps();
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this
            ->hasMany(ProductImage::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * @return HasOne<ProductImage, $this>
     */
    public function featuredImage(): HasOne
    {
        return $this
            ->hasOne(ProductImage::class)
            ->where('is_primary', true);
    }

    /**
     * @return HasOne<ProductVideo, $this>
     */
    public function video(): HasOne
    {
        return $this->hasOne(
            ProductVideo::class,
        );
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopePublished(
        Builder $query,
    ): Builder {
        return $query
            ->where(
                'status',
                ProductStatus::Published->value,
            )
            ->where(
                static function (
                    Builder $query,
                ): void {
                    $query
                        ->whereNull('published_at')
                        ->orWhere(
                            'published_at',
                            '<=',
                            now(),
                        );
                },
            );
    }

    public function isPublished(): bool
    {
        if ($this->status !== ProductStatus::Published) {
            return false;
        }

        return $this->published_at === null
            || $this->published_at->lessThanOrEqualTo(
                now(),
            );
    }
}
