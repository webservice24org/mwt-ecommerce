<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Reviews\Enums\ProductReviewStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ProductReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $user_id
 * @property int|null $verified_order_id
 * @property int|null $moderated_by
 * @property int $rating
 * @property string|null $title
 * @property string $body
 * @property ProductReviewStatus $status
 * @property bool $is_verified_purchase
 * @property string|null $moderation_note
 * @property CarbonImmutable|null $moderated_at
 * @property int|null $moderated_by
 */
final class ProductReview extends Model
{
    /** @use HasFactory<ProductReviewFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'user_id',
        'verified_order_id',
        'moderated_by',
        'rating',
        'title',
        'body',
        'status',
        'is_verified_purchase',
        'moderation_note',
        'moderated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',

            'user_id' => 'integer',

            'verified_order_id' => 'integer',

            'moderated_by' => 'integer',

            'rating' => 'integer',

            'status' => ProductReviewStatus::class,

            'is_verified_purchase' => 'boolean',

            'moderated_at' => 'immutable_datetime',
        ];
    }

    /**
     * Product being reviewed.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
        );
    }

    /**
     * Customer who submitted the review.
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
     * Order that established verified-purchase
     * eligibility for this review.
     *
     * @return BelongsTo<Order, $this>
     */
    public function verifiedOrder(): BelongsTo
    {
        return $this->belongsTo(
            Order::class,
            'verified_order_id',
        );
    }

    public function isApproved(): bool
    {
        return $this->status ===
            ProductReviewStatus::Approved;
    }

    public function isPending(): bool
    {
        return $this->status ===
            ProductReviewStatus::Pending;
    }

    public function isRejected(): bool
    {
        return $this->status ===
            ProductReviewStatus::Rejected;
    }

    /**
     * Admin who last moderated this review.
     *
     * @return BelongsTo<Admin, $this>
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(
            Admin::class,
            'moderated_by',
        );
    }
}
