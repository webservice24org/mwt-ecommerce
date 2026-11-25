<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Bulk\Services;

use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class AiSeoTextFieldWriter
{
    /**
     * @throws ModelNotFoundException
     */
    public function lockEntity(
        AiSeoEntityType $entityType,
        int $entityId,
    ): Product|Category|Brand|Page {
        return match ($entityType) {
            AiSeoEntityType::Product => Product::query()
                ->lockForUpdate()
                ->findOrFail(
                    $entityId,
                ),

            AiSeoEntityType::Category => Category::query()
                ->lockForUpdate()
                ->findOrFail(
                    $entityId,
                ),

            AiSeoEntityType::Brand => Brand::query()
                ->lockForUpdate()
                ->findOrFail(
                    $entityId,
                ),

            AiSeoEntityType::Page => Page::query()
                ->lockForUpdate()
                ->findOrFail(
                    $entityId,
                ),
        };
    }

    public function read(
        Product|Category|Brand|Page $entity,
        AiSeoSuggestionField $field,
    ): ?string {
        $column =
            $this->applicationColumn(
                $field,
            );

        if (
            $this->isEntityField(
                $field,
            )
        ) {
            return $this->nullableString(
                $entity
                    ->getAttribute(
                        $column,
                    ),
            );
        }

        $seoMeta =
            $entity
                ->seoMeta()
                ->lockForUpdate()
                ->first();

        return $this->nullableString(
            $seoMeta
                ?->getAttribute(
                    $column,
                ),
        );
    }

    public function write(
        Product|Category|Brand|Page $entity,
        AiSeoSuggestionField $field,
        string $value,
    ): void {
        $column =
            $this->applicationColumn(
                $field,
            );

        if (
            $this->isEntityField(
                $field,
            )
        ) {
            $entity->setAttribute(
                $column,
                $value,
            );

            $entity->save();

            return;
        }

        /*
         * Only the selected social text column is updated.
         * Canonical, robots and image columns are never included
         * in this write payload.
         */
        $entity
            ->seoMeta()
            ->updateOrCreate(
                [],
                [
                    $column => $value,
                ],
            );
    }

    private function applicationColumn(
        AiSeoSuggestionField $field,
    ): string {
        /*
         * This exhaustive match is the live-write allowlist and
         * the actual persistence-column resolver.
         *
         * No default arm is intentional: if the enum gains a new
         * case later, K.4 fails closed until that field is
         * deliberately mapped here.
         */
        return match ($field->value) {
            'meta_title' => 'meta_title',

            'meta_description' => 'meta_description',

            'og_title' => 'og_title',

            'og_description' => 'og_description',

            'twitter_title' => 'twitter_title',

            'twitter_description' => 'twitter_description',
        };
    }

    private function isEntityField(
        AiSeoSuggestionField $field,
    ): bool {
        return in_array(
            $field->value,
            [
                'meta_title',
                'meta_description',
            ],
            true,
        );
    }

    private function nullableString(
        mixed $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        throw new DomainException(
            'Current SEO field value is not a nullable string.',
        );
    }
}
