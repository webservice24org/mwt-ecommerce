<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Data\SeoAuditCatalogCoverageSummaryData;
use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Models\Category;
use App\Models\Product;
use App\Models\SeoAuditCrawlPage;
use App\Models\SeoAuditRun;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class SeoAuditCatalogCoverageSeeder
{
    public function __construct(
        private SeoAuditUrlNormalizer $urls,
    ) {}

    public function seed(
        SeoAuditRun $run,
        string $canonicalHost,
    ): SeoAuditCatalogCoverageSummaryData {
        return DB::transaction(function () use ($run, $canonicalHost): SeoAuditCatalogCoverageSummaryData {
            $current = SeoAuditRun::query()
                ->lockForUpdate()
                ->findOrFail($run->getKey());

            if ($current->status !== SeoAuditRunStatus::Running) {
                throw new DomainException(
                    'Catalog coverage may only be seeded while the SEO audit run is active.',
                );
            }

            $products = Product::query()
                ->published()
                ->orderBy('id')
                ->limit(SeoAuditCrawlInventoryRecorder::MAX_PAGES_PER_RUN + 1)
                ->get(['id', 'slug']);

            $categories = Category::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->limit(SeoAuditCrawlInventoryRecorder::MAX_PAGES_PER_RUN + 1)
                ->get(['id', 'slug']);

            if (
                $products->count() > SeoAuditCrawlInventoryRecorder::MAX_PAGES_PER_RUN
                || $categories->count() > SeoAuditCrawlInventoryRecorder::MAX_PAGES_PER_RUN
            ) {
                throw new DomainException(
                    'Public catalog coverage exceeds the SEO audit crawl bound.',
                );
            }

            /** @var array<string, array{url:string,subject_type:SeoAuditSubjectType,subject_key:string}> $catalog */
            $catalog = [];

            foreach ($products as $product) {
                $this->addCatalogEntry(
                    catalog: $catalog,
                    reference: route(
                        'products.show',
                        ['slug' => $product->slug],
                        false,
                    ),
                    canonicalHost: $canonicalHost,
                    subjectType: SeoAuditSubjectType::Product,
                    subjectKey: 'product:'.(string) $product->id,
                );
            }

            foreach ($categories as $category) {
                $this->addCatalogEntry(
                    catalog: $catalog,
                    reference: route(
                        'categories.show',
                        ['slug' => $category->slug],
                        false,
                    ),
                    canonicalHost: $canonicalHost,
                    subjectType: SeoAuditSubjectType::Category,
                    subjectKey: 'category:'.(string) $category->id,
                );
            }

            /** @var array<string, SeoAuditCrawlPage> $existing */
            $existing = SeoAuditCrawlPage::query()
                ->where('seo_audit_run_id', $current->getKey())
                ->orderBy('id')
                ->get()
                ->keyBy('url_hash')
                ->all();

            $newCount = 0;

            foreach ($catalog as $urlHash => $_entry) {
                if (! isset($existing[$urlHash])) {
                    $newCount++;
                }
            }

            if (
                count($existing) + $newCount
                > SeoAuditCrawlInventoryRecorder::MAX_PAGES_PER_RUN
            ) {
                throw new DomainException(
                    'Catalog coverage would exceed the SEO audit crawl page limit.',
                );
            }

            $taggedExisting = 0;

            foreach ($catalog as $urlHash => $entry) {
                $page = $existing[$urlHash] ?? null;

                if ($page === null) {
                    SeoAuditCrawlPage::query()->create([
                        'seo_audit_run_id' => $current->getKey(),
                        'url_hash' => $urlHash,
                        'url' => $entry['url'],
                        'state' => SeoAuditCrawlState::Discovered,
                        'depth' => 0,
                        'subject_type' => $entry['subject_type'],
                        'subject_key' => $entry['subject_key'],
                    ]);

                    continue;
                }

                $page->forceFill([
                    'depth' => min($page->depth, 0),
                    'subject_type' => $entry['subject_type'],
                    'subject_key' => $entry['subject_key'],
                ])->save();

                $taggedExisting++;
            }

            return new SeoAuditCatalogCoverageSummaryData(
                publishedProducts: $products->count(),
                activeCategories: $categories->count(),
                catalogUrls: count($catalog),
                newPages: $newCount,
                taggedExistingPages: $taggedExisting,
            );
        });
    }

    /**
     * @param  array<string, array{url:string,subject_type:SeoAuditSubjectType,subject_key:string}>  $catalog
     */
    private function addCatalogEntry(
        array &$catalog,
        string $reference,
        string $canonicalHost,
        SeoAuditSubjectType $subjectType,
        string $subjectKey,
    ): void {
        $url = $this->urls->normalizeInternal(
            reference: $reference,
            canonicalHost: $canonicalHost,
        );

        if ($url === null) {
            throw new DomainException(
                'Public catalog route resolved outside the configured canonical origin.',
            );
        }

        $hash = $this->urls->hash($url);
        $existing = $catalog[$hash] ?? null;

        if (
            $existing !== null
            && (
                $existing['subject_type'] !== $subjectType
                || $existing['subject_key'] !== $subjectKey
            )
        ) {
            throw new DomainException(
                'Multiple public catalog entities resolve to the same SEO audit URL.',
            );
        }

        $catalog[$hash] = [
            'url' => $url,
            'subject_type' => $subjectType,
            'subject_key' => $subjectKey,
        ];
    }
}
