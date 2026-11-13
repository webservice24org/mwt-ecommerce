<?php

declare(strict_types=1);

use App\Domain\Reviews\Enums\ProductReviewStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'product_reviews',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('product_id')
                    ->constrained('products')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                /*
                 * The qualifying order that proves
                 * this was a verified purchase.
                 *
                 * 9.4B will populate this only after
                 * server-side purchase eligibility
                 * has been confirmed.
                 */
                $table
                    ->foreignId('verified_order_id')
                    ->nullable()
                    ->constrained('orders')
                    ->nullOnDelete();

                /*
                 * Admin/customer-service user who
                 * performed the moderation action.
                 */
                $table
                    ->foreignId('moderated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                /*
                 * Rating contract:
                 *
                 * 1 = very poor
                 * 5 = excellent
                 *
                 * Request/domain validation in later
                 * phases will enforce the 1–5 range.
                 */
                $table->unsignedTinyInteger(
                    'rating',
                );

                $table
                    ->string(
                        'title',
                        160,
                    )
                    ->nullable();

                $table->text(
                    'body',
                );

                $table
                    ->string(
                        'status',
                        20,
                    )
                    ->default(
                        ProductReviewStatus::Pending
                            ->value,
                    );

                $table
                    ->boolean(
                        'is_verified_purchase',
                    )
                    ->default(false);

                /*
                 * Internal moderation note.
                 *
                 * This must never be exposed through
                 * the public storefront review query.
                 */
                $table
                    ->text(
                        'moderation_note',
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'moderated_at',
                    )
                    ->nullable();

                $table->timestamps();

                /*
                 * One review per customer/product.
                 *
                 * 9.4C may allow editing the same
                 * review rather than inserting
                 * another record.
                 */
                $table->unique(
                    [
                        'product_id',
                        'user_id',
                    ],
                    'product_reviews_product_user_unique',
                );

                /*
                 * Public product review listing.
                 */
                $table->index(
                    [
                        'product_id',
                        'status',
                        'created_at',
                    ],
                    'product_reviews_public_listing_idx',
                );

                /*
                 * Rating aggregation / distribution.
                 */
                $table->index(
                    [
                        'product_id',
                        'status',
                        'rating',
                    ],
                    'product_reviews_rating_idx',
                );

                /*
                 * Customer review history.
                 */
                $table->index(
                    [
                        'user_id',
                        'status',
                    ],
                    'product_reviews_customer_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'product_reviews',
        );
    }
};
