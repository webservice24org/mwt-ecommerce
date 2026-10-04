<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Registry;

use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\ContentSection;
use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;
use App\Domain\PageBuilder\Sections\FeaturedProductsSection;
use App\Domain\PageBuilder\Sections\HeroSection;
use App\Domain\PageBuilder\Sections\ProductCategoriesSection;
use App\Domain\PageBuilder\Sections\ProductCollectionSection;
use App\Domain\PageBuilder\Sections\PromotionalBannerSection;
use InvalidArgumentException;

final class SectionRegistry
{
    /**
     * @var array<string, SectionDefinition>
     */
    private array $definitions = [];

    public function __construct()
    {
        $this->register(
            new HeroSection,
        );

        $this->register(
            new FeaturedProductsSection,
        );

        $this->register(
            new ProductCategoriesSection,
        );

        $this->register(
            new ProductCollectionSection,
        );

        $this->register(
            new PromotionalBannerSection,
        );

        $this->register(
            new ContentSection,
        );
    }

    /**
     * @return list<SectionDefinition>
     */
    public function all(): array
    {
        return array_values(
            $this->definitions,
        );
    }

    public function has(
        SectionType $type,
    ): bool {
        return isset(
            $this->definitions[
                $type->value
            ],
        );
    }

    public function get(
        SectionType $type,
    ): SectionDefinition {
        $definition =
            $this->definitions[
                $type->value
            ] ?? null;

        if ($definition === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'Page Builder section type [%s] is not registered.',
                    $type->value,
                ),
            );
        }

        return $definition;
    }

    public function supportsTemplate(
        SectionType $type,
        string $template,
    ): bool {
        if (! $this->has($type)) {
            return false;
        }

        foreach (
            $this->get($type)->templates() as $supported
        ) {
            if (
                $supported->key ===
                $template
            ) {
                return true;
            }
        }

        return false;
    }

    private function register(
        SectionDefinition $definition,
    ): void {
        $key =
            $definition
                ->type()
                ->value;

        if (
            isset(
                $this->definitions[
                    $key
                ],
            )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Page Builder section type [%s] is already registered.',
                    $key,
                ),
            );
        }

        $this->definitions[
            $key
        ] = $definition;
    }
}
