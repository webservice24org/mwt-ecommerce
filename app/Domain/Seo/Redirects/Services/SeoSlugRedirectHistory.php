<?php

declare(strict_types=1);

namespace App\Domain\Seo\Redirects\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class SeoSlugRedirectHistory
{
    public const PRODUCT = 'product';

    public const CATEGORY = 'category';

    public const BRAND = 'brand';

    /**
     * Persist one old-slug -> current-slug relationship.
     *
     * Existing history for the same entity is rewritten to the newest slug so
     * historical URLs do not form redirect chains after multiple slug changes.
     */
    public function record(
        string $entityType,
        int $entityId,
        string $oldSlug,
        string $newSlug,
    ): void {
        $entityType = $this->entityType($entityType);
        $oldSlug = trim($oldSlug);
        $newSlug = trim($newSlug);

        if (
            $entityId < 1
            || $oldSlug === ''
            || $newSlug === ''
            || $oldSlug === $newSlug
        ) {
            return;
        }

        $now = now();

        DB::table('seo_slug_redirects')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->update([
                'new_slug' => $newSlug,
                'updated_at' => $now,
            ]);

        DB::table('seo_slug_redirects')
            ->updateOrInsert(
                [
                    'entity_type' => $entityType,
                    'old_slug' => $oldSlug,
                ],
                [
                    'entity_id' => $entityId,
                    'new_slug' => $newSlug,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
    }

    public function redirectResponse(
        string $entityType,
        string $oldSlug,
    ): ?RedirectResponse {
        $path = $this->redirectPath(
            entityType: $entityType,
            oldSlug: $oldSlug,
        );

        if ($path === null) {
            return null;
        }

        // Permanent, internal-only slug-history redirect. Never redirect away.
        return redirect()->to($path, 301);
    }

    public function redirectPath(
        string $entityType,
        string $oldSlug,
    ): ?string {
        $entityType = $this->entityType($entityType);
        $oldSlug = trim($oldSlug);

        if ($oldSlug === '') {
            return null;
        }

        $newSlug = DB::table('seo_slug_redirects')
            ->where('entity_type', $entityType)
            ->where('old_slug', $oldSlug)
            ->value('new_slug');

        if (! is_string($newSlug) || trim($newSlug) === '') {
            return null;
        }

        $encodedSlug = rawurlencode($newSlug);

        return match ($entityType) {
            self::PRODUCT => '/products/'.$encodedSlug,
            self::CATEGORY => '/category/'.$encodedSlug,
            self::BRAND => '/brand/'.$encodedSlug,
            default => throw new LogicException(
                'Validated SEO slug redirect entity type became unsupported.',
            ),
        };
    }

    public function isReserved(
        string $entityType,
        string $slug,
    ): bool {
        $entityType = $this->entityType($entityType);
        $slug = trim($slug);

        if ($slug === '') {
            return false;
        }

        return DB::table('seo_slug_redirects')
            ->where('entity_type', $entityType)
            ->where('old_slug', $slug)
            ->exists();
    }

    private function entityType(string $entityType): string
    {
        return match ($entityType) {
            self::PRODUCT,
            self::CATEGORY,
            self::BRAND => $entityType,
            default => throw new InvalidArgumentException(
                'Unsupported SEO slug redirect entity type.',
            ),
        };
    }
}
