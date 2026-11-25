<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Audit;

use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditImageHealthState;
use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Domain\Seo\Audit\Services\SeoAuditImageHealthEnricher;
use App\Domain\Seo\Audit\Services\SeoAuditRunLifecycle;
use App\Domain\Seo\Audit\Services\SeoAuditTechnicalRuleEvaluator;
use App\Models\Admin;
use App\Models\SeoAuditCrawlPage;
use App\Models\SeoAuditImageResource;
use App\Models\SeoAuditPageImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SeoAuditImageHealthEnricherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_enriches_same_origin_image_health_and_activates_broken_image_rule(): void
    {
        Http::fake([
            'https://example.test/good.jpg' => Http::response(
                '',
                200,
                ['Content-Type' => 'image/jpeg'],
            ),
            'https://example.test/missing.jpg' => Http::response(
                '',
                404,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $lifecycle = app(SeoAuditRunLifecycle::class);
        $run = $lifecycle->start(
            $lifecycle->request(Admin::factory()->create()),
        );

        $page = SeoAuditCrawlPage::query()->create([
            'seo_audit_run_id' => $run->id,
            'url_hash' => hash('sha256', 'https://example.test/'),
            'url' => 'https://example.test/',
            'state' => SeoAuditCrawlState::Fetched,
            'depth' => 0,
            'subject_type' => SeoAuditSubjectType::Url,
            'http_status' => 200,
            'content_type' => 'text/html',
            'canonical_url' => 'https://example.test/',
            'is_indexable' => true,
            'title' => 'Home',
            'meta_description' => 'Store home',
            'h1_count' => 1,
            'word_count' => 300,
            'fetched_at' => now(),
        ]);

        foreach ([
            ['https://example.test/good.jpg', true],
            ['https://example.test/missing.jpg', true],
            ['https://cdn.example.invalid/external.jpg', true],
        ] as [$url, $altPresent]) {
            SeoAuditPageImage::query()->create([
                'seo_audit_run_id' => $run->id,
                'source_page_id' => $page->id,
                'observation_hash' => hash('sha256', $url.'|1'),
                'image_url_hash' => hash('sha256', $url),
                'image_url' => $url,
                'alt_attribute_present' => $altPresent,
                'alt_text' => 'Image',
                'occurrences' => 1,
            ]);
        }

        $summary = app(SeoAuditImageHealthEnricher::class)->enrich(
            run: $run,
            canonicalHost: 'https://example.test',
        );

        self::assertSame(3, $summary->observations);
        self::assertSame(3, $summary->resources);
        self::assertSame(1, $summary->healthy);
        self::assertSame(1, $summary->broken);
        self::assertSame(1, $summary->skippedExternal);

        self::assertSame(
            SeoAuditImageHealthState::Broken,
            SeoAuditImageResource::query()
                ->where('image_url_hash', hash('sha256', 'https://example.test/missing.jpg'))
                ->firstOrFail()
                ->state,
        );

        Http::assertNotSent(
            static fn (Request $request): bool => $request->url() === 'https://cdn.example.invalid/external.jpg',
        );

        $evaluation = app(SeoAuditTechnicalRuleEvaluator::class)
            ->evaluate($run);

        self::assertSame(16, $evaluation->rulesEvaluated);
        self::assertNotContains(
            SeoAuditRule::BrokenImage,
            $evaluation->deferredRules,
        );
        self::assertTrue(
            $run->findings()
                ->where('rule_key', SeoAuditRule::BrokenImage->value)
                ->where('url', 'https://example.test/missing.jpg')
                ->exists(),
        );
        self::assertFalse(
            $run->findings()
                ->where('rule_key', SeoAuditRule::BrokenImage->value)
                ->where('url', 'https://cdn.example.invalid/external.jpg')
                ->exists(),
        );
    }
}
