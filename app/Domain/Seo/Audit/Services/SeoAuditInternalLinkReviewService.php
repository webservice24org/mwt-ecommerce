<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Enums\SeoAuditInternalLinkReviewState;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Models\Admin;
use App\Models\SeoAuditInternalLinkOpportunity;
use App\Models\SeoAuditRun;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class SeoAuditInternalLinkReviewService
{
    public function review(
        SeoAuditRun $run,
        string $fingerprint,
        SeoAuditInternalLinkReviewState $state,
        Admin $admin,
        ?string $note,
    ): SeoAuditInternalLinkOpportunity {
        if ($state === SeoAuditInternalLinkReviewState::Pending) {
            throw new DomainException(
                'Pending is not a valid review decision.',
            );
        }

        return DB::transaction(function () use (
            $run,
            $fingerprint,
            $state,
            $admin,
            $note,
        ): SeoAuditInternalLinkOpportunity {
            $currentRun = SeoAuditRun::query()
                ->lockForUpdate()
                ->findOrFail($run->getKey());

            if ($currentRun->status !== SeoAuditRunStatus::Completed) {
                throw new DomainException(
                    'Internal link opportunities may only be reviewed for a completed SEO audit run.',
                );
            }

            $opportunity = SeoAuditInternalLinkOpportunity::query()
                ->where(
                    'seo_audit_run_id',
                    $currentRun->getKey(),
                )
                ->where('fingerprint', $fingerprint)
                ->lockForUpdate()
                ->firstOrFail();

            if ($opportunity->review_state === $state) {
                return $opportunity;
            }

            if (
                $opportunity->review_state
                !== SeoAuditInternalLinkReviewState::Pending
            ) {
                throw new DomainException(
                    'This internal link opportunity already has a final review decision.',
                );
            }

            $normalizedNote = $note === null
                ? null
                : trim($note);

            $opportunity->forceFill([
                'review_state' => $state,
                'reviewed_by_admin_id' => $admin->getKey(),
                'reviewed_by_name' => $admin->name,
                'reviewed_by_email' => $admin->email,
                'reviewed_at' => now(),
                'review_note' => $normalizedNote === ''
                    ? null
                    : $normalizedNote,
            ])->save();

            return $opportunity->refresh();
        });
    }
}
