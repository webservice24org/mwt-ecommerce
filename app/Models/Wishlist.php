<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\WishlistFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Product $product
 */
final class Wishlist extends Model
{
    /** @use HasFactory<WishlistFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',

            'product_id' => 'integer',

            'created_at' => 'immutable_datetime',

            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * Customer who owns the wishlist entry.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
        );
    }

    /**
     * Product saved in the wishlist.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
        );
    }
}
