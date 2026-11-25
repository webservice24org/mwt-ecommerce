<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Data\SeoAuditInternalLinkOpportunitySummaryData;
use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditInternalLinkReviewState;
use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Models\SeoAuditCrawlPage;
use App\Models\SeoAuditFinding;
use App\Models\SeoAuditInternalLink;
use App\Models\SeoAuditInternalLinkOpportunity;
use App\Models\SeoAuditRun;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class SeoAuditInternalLinkOpportunityBuilder
{
    public const ENGINE_VERSION = 'seo-internal-links-v1';

    public const MAX_TARGETS_PER_RUN = 250;

    public const MAX_SOURCE_CANDIDATES = 1000;

    public const MAX_SOURCES_PER_TARGET = 2;

    public const MAX_OPPORTUNITIES_PER_RUN = 500;

    public function rebuild(
        SeoAuditRun $run,
    ): SeoAuditInternalLinkOpportunitySummaryData {
        return DB::transaction(function () use ($run): SeoAuditInternalLinkOpportunitySummaryData {
            $current = SeoAuditRun::query()
                ->lockForUpdate()
                ->findOrFail($run->getKey());

            if ($current->status !== SeoAuditRunStatus::Completed) {
                throw new DomainException(
                    'Internal linking opportunities may only be built from a completed SEO audit run.',
                );
            }

            $orphanQuery = SeoAuditFinding::query()
                ->where(
                    'seo_audit_run_id',
                    $current->getKey(),
                )
                ->whereIn(
                    'rule_key',
                    [
                        SeoAuditRule::OrphanProduct->value,
                        SeoAuditRule::OrphanCategory->value,
                    ],
                );

            $orphanTargets = (clone $orphanQuery)->count();
            $truncated = $orphanTargets > self::MAX_TARGETS_PER_RUN;

            $findings = $orphanQuery
                ->orderBy('id')
                ->limit(self::MAX_TARGETS_PER_RUN)
                ->get();

            /** @var array<string, true> $subjectKeys */
            $subjectKeys = [];

            foreach ($findings as $finding) {
                if ($finding->subject_key !== null) {
                    $subjectKeys[$finding->subject_key] = true;
                }
            }

            /** @var array<string, SeoAuditCrawlPage> $targetsBySubject */
            $targetsBySubject = [];

            if ($subjectKeys !== []) {
                $targetPages = SeoAuditCrawlPage::query()
                    ->where(
                        'seo_audit_run_id',
                        $current->getKey(),
                    )
                    ->whereIn(
                        'subject_type',
                        [
                            SeoAuditSubjectType::Product->value,
                            SeoAuditSubjectType::Category->value,
                        ],
                    )
                    ->whereIn(
                        'subject_key',
                        array_keys($subjectKeys),
                    )
                    ->orderBy('id')
                    ->get();

                foreach ($targetPages as $targetPage) {
                    if ($targetPage->subject_key !== null) {
                        $targetsBySubject[$targetPage->subject_key] = $targetPage;
                    }
                }
            }

            $sources = SeoAuditCrawlPage::query()
                ->where(
                    'seo_audit_run_id',
                    $current->getKey(),
                )
                ->where(
                    'state',
                    SeoAuditCrawlState::Fetched->value,
                )
                ->where('http_status', 200)
                ->where('is_indexable', true)
                ->whereIn(
                    'subject_type',
                    [
                        SeoAuditSubjectType::Category->value,
                        SeoAuditSubjectType::Brand->value,
                        SeoAuditSubjectType::Page->value,
                        SeoAuditSubjectType::Url->value,
                        SeoAuditSubjectType::Product->value,
                    ],
                )
                ->orderBy('depth')
                ->orderBy('id')
                ->limit(self::MAX_SOURCE_CANDIDATES)
                ->get();

            $links = SeoAuditInternalLink::query()
                ->where(
                    'seo_audit_run_id',
                    $current->getKey(),
                )
                ->orderBy('id')
                ->limit(
                    SeoAuditCrawlInventoryRecorder::MAX_LINKS_PER_RUN + 1,
                )
                ->get([
                    'source_page_id',
                    'target_page_id',
                    'nofollow',
                ]);

            if (
                $links->count()
                > SeoAuditCrawlInventoryRecorder::MAX_LINKS_PER_RUN
            ) {
                throw new DomainException(
                    'Observed internal link graph exceeds the SEO audit bound.',
                );
            }

            /** @var array<string, true> $existingEdges */
            $existingEdges = [];

            /** @var array<int, int> $dofollowInboundCounts */
            $dofollowInboundCounts = [];

            foreach ($links as $link) {
                $existingEdges[
                    $this->edgeKey(
                        $link->source_page_id,
                        $link->target_page_id,
                    )
                ] = true;

                if (! $link->nofollow) {
                    $dofollowInboundCounts[$link->target_page_id] =
                        ($dofollowInboundCounts[$link->target_page_id] ?? 0) + 1;
                }
            }

            /** @var array<string, true> $reviewedFingerprints */
            $reviewedFingerprints = [];

            $reviewed = SeoAuditInternalLinkOpportunity::query()
                ->where(
                    'seo_audit_run_id',
                    $current->getKey(),
                )
                ->where(
                    'review_state',
                    '!=',
                    SeoAuditInternalLinkReviewState::Pending->value,
                )
                ->orderBy('id')
                ->get(['fingerprint']);

            foreach ($reviewed as $reviewedOpportunity) {
                $reviewedFingerprints[$reviewedOpportunity->fingerprint] = true;
            }

            SeoAuditInternalLinkOpportunity::query()
                ->where(
                    'seo_audit_run_id',
                    $current->getKey(),
                )
                ->where(
                    'review_state',
                    SeoAuditInternalLinkReviewState::Pending->value,
                )
                ->delete();

            $opportunities = 0;
            $evaluatedTargets = 0;
            $targetsWithoutCandidates = 0;

            foreach ($findings as $finding) {
                $subjectKey = $finding->subject_key;

                if ($subjectKey === null) {
                    $targetsWithoutCandidates++;

                    continue;
                }

                $target = $targetsBySubject[$subjectKey] ?? null;

                if (
                    $target === null
                    || $target->state !== SeoAuditCrawlState::Fetched
                    || $target->http_status !== 200
                    || $target->is_indexable !== true
                ) {
                    $targetsWithoutCandidates++;

                    continue;
                }

                $evaluatedTargets++;

                /**
                 * @var list<array{source:SeoAuditCrawlPage,score:int,inbound:int}> $ranked
                 */
                $ranked = [];

                foreach ($sources as $source) {
                    if ($source->getKey() === $target->getKey()) {
                        continue;
                    }

                    if (
                        isset(
                            $existingEdges[
                                $this->edgeKey(
                                    $source->getKey(),
                                    $target->getKey(),
                                )
                            ],
                        )
                    ) {
                        continue;
                    }

                    $inbound = $dofollowInboundCounts[$source->getKey()] ?? 0;
                    $score = $this->score(
                        source: $source,
                        target: $target,
                        dofollowInbound: $inbound,
                    );

                    if ($score <= 0) {
                        continue;
                    }

                    $ranked[] = [
                        'source' => $source,
                        'score' => $score,
                        'inbound' => $inbound,
                    ];
                }

                usort(
                    $ranked,
                    static function (array $left, array $right): int {
                        if ($left['score'] !== $right['score']) {
                            return $right['score'] <=> $left['score'];
                        }

                        if ($left['source']->depth !== $right['source']->depth) {
                            return $left['source']->depth <=> $right['source']->depth;
                        }

                        return $left['source']->getKey() <=> $right['source']->getKey();
                    },
                );

                $selected = array_slice(
                    $ranked,
                    0,
                    self::MAX_SOURCES_PER_TARGET,
                );

                $createdForTarget = 0;

                foreach ($selected as $candidate) {
                    if ($opportunities >= self::MAX_OPPORTUNITIES_PER_RUN) {
                        $truncated = true;
                        break 2;
                    }

                    $source = $candidate['source'];
                    $fingerprint = $this->fingerprint(
                        run: $current,
                        source: $source,
                        target: $target,
                    );

                    if (isset($reviewedFingerprints[$fingerprint])) {
                        continue;
                    }

                    SeoAuditInternalLinkOpportunity::query()->create([
                        'seo_audit_run_id' => $current->getKey(),
                        'source_page_id' => $source->getKey(),
                        'target_page_id' => $target->getKey(),
                        'fingerprint' => $fingerprint,
                        'engine_version' => self::ENGINE_VERSION,
                        'review_state' => SeoAuditInternalLinkReviewState::Pending,
                        'score' => $candidate['score'],
                        'target_subject_type' => $target->subject_type,
                        'target_subject_key' => $target->subject_key,
                        'source_url' => $source->url,
                        'target_url' => $target->url,
                        'suggested_anchor' => $this->suggestedAnchor($target),
                        'reason' => $this->reason(
                            finding: $finding,
                            source: $source,
                            inbound: $candidate['inbound'],
                        ),
                    ]);

                    $opportunities++;
                    $createdForTarget++;
                }

                if ($createdForTarget === 0) {
                    $targetsWithoutCandidates++;
                }
            }

            return new SeoAuditInternalLinkOpportunitySummaryData(
                orphanTargets: $orphanTargets,
                evaluatedTargets: $evaluatedTargets,
                opportunities: $opportunities,
                targetsWithoutCandidates: $targetsWithoutCandidates,
                truncated: $truncated,
            );
        });
    }

    private function edgeKey(
        int $sourcePageId,
        int $targetPageId,
    ): string {
        return $sourcePageId.':'.$targetPageId;
    }

    private function fingerprint(
        SeoAuditRun $run,
        SeoAuditCrawlPage $source,
        SeoAuditCrawlPage $target,
    ): string {
        return hash(
            'sha256',
            implode('|', [
                self::ENGINE_VERSION,
                $run->public_id,
                $source->url_hash,
                $target->url_hash,
            ]),
        );
    }

    private function score(
        SeoAuditCrawlPage $source,
        SeoAuditCrawlPage $target,
        int $dofollowInbound,
    ): int {
        $typeScore = match ($target->subject_type) {
            SeoAuditSubjectType::Product => match ($source->subject_type) {
                SeoAuditSubjectType::Category => 100,
                SeoAuditSubjectType::Brand => 90,
                SeoAuditSubjectType::Page => 75,
                SeoAuditSubjectType::Url => 65,
                SeoAuditSubjectType::Product => 30,
                default => 0,
            },
            SeoAuditSubjectType::Category => match ($source->subject_type) {
                SeoAuditSubjectType::Page => 100,
                SeoAuditSubjectType::Url => 95,
                SeoAuditSubjectType::Category => 80,
                SeoAuditSubjectType::Brand => 70,
                SeoAuditSubjectType::Product => 20,
                default => 0,
            },
            default => 0,
        };

        if ($typeScore === 0) {
            return 0;
        }

        $authorityBoost = min(25, max(0, $dofollowInbound));
        $depthPenalty = min(30, max(0, $source->depth) * 5);

        return max(
            1,
            min(
                150,
                $typeScore + $authorityBoost - $depthPenalty,
            ),
        );
    }

    private function suggestedAnchor(
        SeoAuditCrawlPage $target,
    ): ?string {
        $title = trim((string) ($target->title ?? ''));

        if ($title === '') {
            return null;
        }

        return Str::limit(
            $title,
            160,
            '',
        );
    }

    private function reason(
        SeoAuditFinding $finding,
        SeoAuditCrawlPage $source,
        int $inbound,
    ): string {
        $targetLabel = match ($finding->rule_key) {
            SeoAuditRule::OrphanProduct => 'orphan product',
            SeoAuditRule::OrphanCategory => 'orphan category',
            default => 'orphan page',
        };

        return sprintf(
            'Structural opportunity for %s: source type %s at crawl depth %d with %d observed dofollow inbound link(s). Review context and anchor text before publishing.',
            $targetLabel,
            $source->subject_type->value,
            $source->depth,
            $inbound,
        );
    }
}
