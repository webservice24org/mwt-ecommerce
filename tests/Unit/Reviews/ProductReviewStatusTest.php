<?php

declare(strict_types=1);

namespace Tests\Unit\Reviews;

use App\Domain\Reviews\Enums\ProductReviewStatus;
use PHPUnit\Framework\TestCase;

final class ProductReviewStatusTest extends TestCase
{
    public function test_status_values_are_stable(): void
    {
        $this->assertSame(
            'pending',
            ProductReviewStatus::Pending
                ->value,
        );

        $this->assertSame(
            'approved',
            ProductReviewStatus::Approved
                ->value,
        );

        $this->assertSame(
            'rejected',
            ProductReviewStatus::Rejected
                ->value,
        );
    }

    public function test_only_approved_reviews_are_public(): void
    {
        $this->assertFalse(
            ProductReviewStatus::Pending
                ->isPublic(),
        );

        $this->assertTrue(
            ProductReviewStatus::Approved
                ->isPublic(),
        );

        $this->assertFalse(
            ProductReviewStatus::Rejected
                ->isPublic(),
        );
    }
}
