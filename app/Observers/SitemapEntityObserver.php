<?php

declare(strict_types=1);

namespace App\Observers;

use App\Domain\Seo\Sitemap\Services\SitemapInvalidator;
use Illuminate\Database\Eloquent\Model;

final readonly class SitemapEntityObserver
{
    public function __construct(
        private SitemapInvalidator $sitemap,
    ) {}

    public function created(
        Model $model,
    ): void {
        $this->sitemap
            ->invalidate();
    }

    public function updated(
        Model $model,
    ): void {
        $this->sitemap
            ->invalidate();
    }

    public function deleted(
        Model $model,
    ): void {
        $this->sitemap
            ->invalidate();
    }

    public function restored(
        Model $model,
    ): void {
        $this->sitemap
            ->invalidate();
    }

    public function forceDeleted(
        Model $model,
    ): void {
        $this->sitemap
            ->invalidate();
    }
}
