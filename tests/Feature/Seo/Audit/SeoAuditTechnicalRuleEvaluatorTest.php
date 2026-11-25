<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Audit;

use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Domain\Seo\Audit\Services\SeoAuditRunLifecycle;
use App\Domain\Seo\Audit\Services\SeoAuditTechnicalRuleEvaluator;
use App\Models\Admin;
use App\Models\SeoAuditCrawlPage;
use App\Models\SeoAuditInternalLink;
use App\Models\SeoAuditPageImage;
use App\Models\SeoAuditRun;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoAuditTechnicalRuleEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_replaces_deterministic_findings_from_settled_crawl_observations(): void
    {
        $lifecycle = app(SeoAuditRunLifecycle::class);
        $run = $lifecycle->start(
            $lifecycle->request(Admin::factory()->create()),
        );

        $home = $this->page($run, 'https://example.test/', [
            'depth' => 0,
            'canonical_url' => 'https://example.test/',
            'title' => 'Store home',
            'meta_description' => 'Store description',
            'h1_count' => 1,
        ]);

        $missing = $this->page($run, 'https://example.test/missing', [
            'state' => SeoAuditCrawlState::Failed,
            'http_status' => 404,
            'is_indexable' => false,
            'title' => null,
            'meta_description' => null,
            'h1_count' => null,
            'word_count' => null,
            'fetch_error_code' => 'http_404',
        ]);

        $this->link($run, $home, $missing, 'Missing');

        $legacy = $this->page($run, 'https://example.test/legacy', [
            'http_status' => 301,
            'is_indexable' => false,
            'redirect_url' => 'https://example.test/middle',
        ]);
        $middle = $this->page($run, 'https://example.test/middle', [
            'http_status' => 302,
            'is_indexable' => false,
            'redirect_url' => 'https://example.test/final',
        ]);
        $this->page($run, 'https://example.test/final', [
            'canonical_url' => 'https://example.test/final',
            'title' => 'Final',
            'meta_description' => 'Final description',
            'h1_count' => 1,
        ]);

        self::assertNotNull($legacy->redirect_url);
        self::assertNotNull($middle->redirect_url);

        $this->page($run, 'https://example.test/a', [
            'canonical_url' => 'https://example.test/final',
            'title' => 'Shared title',
            'meta_description' => 'Shared description',
            'h1_count' => 2,
        ]);
        $this->page($run, 'https://example.test/b', [
            'canonical_url' => 'https://example.test/final',
            'title' => 'Shared title',
            'meta_description' => 'Shared description',
            'h1_count' => 1,
        ]);

        $this->page($run, 'https://example.test/canonical-bad', [
            'canonical_url' => 'https://example.test/missing',
            'title' => 'Bad canonical target',
            'meta_description' => 'Bad canonical description',
            'h1_count' => 1,
        ]);

        $category = $this->page($run, 'https://example.test/category/thin', [
            'subject_type' => SeoAuditSubjectType::Category,
            'subject_key' => 'category:10',
            'canonical_url' => null,
            'title' => null,
            'meta_description' => null,
            'h1_count' => 0,
            'word_count' => 40,
        ]);

        $this->page($run, 'https://example.test/products/orphan', [
            'subject_type' => SeoAuditSubjectType::Product,
            'subject_key' => 'product:20',
            'canonical_url' => 'https://example.test/products/orphan',
            'title' => 'Orphan product',
            'meta_description' => 'Product description',
            'h1_count' => 1,
        ]);

        SeoAuditPageImage::query()->create([
            'seo_audit_run_id' => $run->id,
            'source_page_id' => $home->id,
            'observation_hash' => hash('sha256', 'missing-alt'),
            'image_url_hash' => hash('sha256', 'https://example.test/image.jpg'),
            'image_url' => 'https://example.test/image.jpg',
            'alt_attribute_present' => false,
            'alt_text' => null,
            'occurrences' => 1,
        ]);

        $summary = app(SeoAuditTechnicalRuleEvaluator::class)
            ->evaluate($run);

        self::assertSame(15, $summary->rulesEvaluated);
        self::assertContains(SeoAuditRule::BrokenImage, $summary->deferredRules);
        self::assertGreaterThan(0, $summary->findingsRecorded);

        /** @var list<string> $ruleKeys */
        $ruleKeys = $run->findings()
            ->pluck('rule_key')
            ->map(static fn (mixed $rule): string => $rule instanceof SeoAuditRule ? $rule->value : (string) $rule)
            ->all();

        foreach ([
            SeoAuditRule::BrokenInternalLink,
            SeoAuditRule::RedirectChain,
            SeoAuditRule::DuplicateCanonical,
            SeoAuditRule::CanonicalToNon200,
            SeoAuditRule::IndexableMissingCanonical,
            SeoAuditRule::IndexableMissingTitle,
            SeoAuditRule::EmptyDescription,
            SeoAuditRule::DuplicateTitle,
            SeoAuditRule::DuplicateDescription,
            SeoAuditRule::MissingH1,
            SeoAuditRule::MultipleH1,
            SeoAuditRule::OrphanProduct,
            SeoAuditRule::OrphanCategory,
            SeoAuditRule::MissingAlt,
            SeoAuditRule::ThinCategoryPage,
        ] as $rule) {
            self::assertContains($rule->value, $ruleKeys);
        }

        self::assertNotContains(SeoAuditRule::BrokenImage->value, $ruleKeys);

        $category->forceFill([
            'canonical_url' => $category->url,
            'title' => 'Useful category',
            'meta_description' => 'Useful category description',
            'h1_count' => 1,
            'word_count' => 250,
        ])->save();

        $this->link($run, $home, $category, 'Category');

        app(SeoAuditTechnicalRuleEvaluator::class)->evaluate($run);

        /** @var list<string> $categoryRules */
        $categoryRules = $run->findings()
            ->where('subject_key', 'category:10')
            ->pluck('rule_key')
            ->map(static fn (mixed $rule): string => $rule instanceof SeoAuditRule ? $rule->value : (string) $rule)
            ->all();

        self::assertNotContains(SeoAuditRule::IndexableMissingCanonical->value, $categoryRules);
        self::assertNotContains(SeoAuditRule::IndexableMissingTitle->value, $categoryRules);
        self::assertNotContains(SeoAuditRule::EmptyDescription->value, $categoryRules);
        self::assertNotContains(SeoAuditRule::MissingH1->value, $categoryRules);
        self::assertNotContains(SeoAuditRule::ThinCategoryPage->value, $categoryRules);
        self::assertNotContains(SeoAuditRule::OrphanCategory->value, $categoryRules);
    }

    public function test_it_refuses_to_evaluate_before_crawl_inventory_is_settled(): void
    {
        $this->expectException(DomainException::class);

        $lifecycle = app(SeoAuditRunLifecycle::class);
        $run = $lifecycle->start(
            $lifecycle->request(Admin::factory()->create()),
        );

        SeoAuditCrawlPage::query()->create([
            'seo_audit_run_id' => $run->id,
            'url_hash' => hash('sha256', 'https://example.test/pending'),
            'url' => 'https://example.test/pending',
            'state' => SeoAuditCrawlState::Discovered,
            'depth' => 1,
            'subject_type' => SeoAuditSubjectType::Url,
        ]);

        app(SeoAuditTechnicalRuleEvaluator::class)->evaluate($run);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function page(
        SeoAuditRun $run,
        string $url,
        array $overrides = [],
    ): SeoAuditCrawlPage {
        return SeoAuditCrawlPage::query()->create([
            'seo_audit_run_id' => $run->id,
            'url_hash' => hash('sha256', $url),
            'url' => $url,
            'state' => SeoAuditCrawlState::Fetched,
            'depth' => 1,
            'subject_type' => SeoAuditSubjectType::Url,
            'subject_key' => null,
            'http_status' => 200,
            'content_type' => 'text/html',
            'redirect_url' => null,
            'canonical_url' => $url,
            'is_indexable' => true,
            'title' => 'Unique '.hash('crc32b', $url),
            'meta_description' => 'Description '.hash('crc32b', $url),
            'h1_count' => 1,
            'word_count' => 300,
            'fetch_error_code' => null,
            'fetch_error_message' => null,
            'fetched_at' => now(),
            ...$overrides,
        ]);
    }

    private function link(
        SeoAuditRun $run,
        SeoAuditCrawlPage $source,
        SeoAuditCrawlPage $target,
        string $anchor,
    ): SeoAuditInternalLink {
        $edgeHash = hash(
            'sha256',
            $source->url."\0".$target->url."\0follow",
        );

        return SeoAuditInternalLink::query()->updateOrCreate(
            [
                'seo_audit_run_id' => $run->id,
                'edge_hash' => $edgeHash,
            ],
            [
                'source_page_id' => $source->id,
                'target_page_id' => $target->id,
                'target_url_hash' => $target->url_hash,
                'target_url' => $target->url,
                'anchor_text' => $anchor,
                'nofollow' => false,
                'occurrences' => 1,
            ],
        );
    }
}
