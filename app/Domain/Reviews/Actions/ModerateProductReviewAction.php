<?php

declare(strict_types=1);

namespace App\Domain\Reviews\Actions;

use App\Domain\Reviews\Enums\ProductReviewStatus;
use App\Models\Admin;
use App\Models\ProductReview;
use Illuminate\Support\Facades\DB;

final readonly class ModerateProductReviewAction
{
    public function approve(
        ProductReview $review,
        Admin $admin,
        ?string $note = null,
    ): ProductReview {
        return $this->moderate(
            review: $review,

            admin: $admin,

            status: ProductReviewStatus::Approved,

            note: $note,
        );
    }

    public function reject(
        ProductReview $review,
        Admin $admin,
        ?string $note = null,
    ): ProductReview {
        return $this->moderate(
            review: $review,

            admin: $admin,

            status: ProductReviewStatus::Rejected,

            note: $note,
        );
    }

    private function moderate(
        ProductReview $review,
        Admin $admin,
        ProductReviewStatus $status,
        ?string $note,
    ): ProductReview {
        return DB::transaction(
            function () use ($review, $admin, $status, $note): ProductReview {
                $lockedReview =
                    ProductReview::query()
                        ->whereKey(
                            $review->id,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $lockedReview->update([
                    'status' => $status,

                    'moderated_by' => $admin->id,

                    'moderation_note' => $note,

                    'moderated_at' => now(),
                ]);

                return $lockedReview
                    ->refresh();
            },
        );
    }
}
