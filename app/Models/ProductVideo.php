<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Catalog\Enums\ProductVideoType;
use Database\Factories\ProductVideoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property ProductVideoType $type
 * @property string|null $path
 * @property string|null $url
 * @property string|null $original_name
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property string|null $title
 */
final class ProductVideo extends Model
{
    /** @use HasFactory<ProductVideoFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'type',
        'path',
        'url',
        'original_name',
        'mime_type',
        'file_size',
        'title',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProductVideoType::class,
            'file_size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
        );
    }
}
