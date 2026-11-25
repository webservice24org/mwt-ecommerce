<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Enums\SeoAuditSeverity;

final class SeoAuditRuleRegistry
{
    public const VERSION = 'seo-audit-rules-v1';

    public function defaultSeverity(SeoAuditRule $rule): SeoAuditSeverity
    {
        return match ($rule) {
            SeoAuditRule::BrokenInternalLink,
            SeoAuditRule::DuplicateCanonical,
            SeoAuditRule::CanonicalToNon200,
            SeoAuditRule::IndexableMissingCanonical,
            SeoAuditRule::IndexableMissingTitle,
            SeoAuditRule::MissingH1,
            SeoAuditRule::BrokenImage => SeoAuditSeverity::Error,

            SeoAuditRule::OrphanProduct,
            SeoAuditRule::OrphanCategory,
            SeoAuditRule::RedirectChain,
            SeoAuditRule::EmptyDescription,
            SeoAuditRule::DuplicateTitle,
            SeoAuditRule::DuplicateDescription,
            SeoAuditRule::MultipleH1,
            SeoAuditRule::MissingAlt,
            SeoAuditRule::ThinCategoryPage => SeoAuditSeverity::Warning,
        };
    }

    public function label(SeoAuditRule $rule): string
    {
        return match ($rule) {
            SeoAuditRule::OrphanProduct => 'Orphan product',
            SeoAuditRule::OrphanCategory => 'Orphan category',
            SeoAuditRule::BrokenInternalLink => 'Broken internal link',
            SeoAuditRule::RedirectChain => 'Redirect chain',
            SeoAuditRule::DuplicateCanonical => 'Duplicate canonical',
            SeoAuditRule::CanonicalToNon200 => 'Canonical points to non-200 URL',
            SeoAuditRule::IndexableMissingCanonical => 'Indexable page missing canonical',
            SeoAuditRule::IndexableMissingTitle => 'Indexable page missing title',
            SeoAuditRule::EmptyDescription => 'Empty meta description',
            SeoAuditRule::DuplicateTitle => 'Duplicate title',
            SeoAuditRule::DuplicateDescription => 'Duplicate meta description',
            SeoAuditRule::MissingH1 => 'Missing H1',
            SeoAuditRule::MultipleH1 => 'Multiple unintended H1 elements',
            SeoAuditRule::BrokenImage => 'Broken image',
            SeoAuditRule::MissingAlt => 'Missing image alt text',
            SeoAuditRule::ThinCategoryPage => 'Thin category page',
        };
    }
}
