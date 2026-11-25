<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\AiGovernance;

use App\Domain\Seo\AiGovernance\Support\SeoAiInputMinimizer;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SeoAiInputMinimizerTest extends TestCase
{
    #[Test]
    public function it_builds_a_deterministic_hash_from_allowlisted_product_content(): void
    {
        $minimizer = new SeoAiInputMinimizer;

        $first = $minimizer->minimize([
            'title' => '  Red Shoe  ',
            'attributes' => [
                'size' => 42,
                'material' => 'Leather',
            ],
            'slug' => 'red-shoe',
        ]);

        $second = $minimizer->minimize([
            'slug' => 'red-shoe',
            'attributes' => [
                'material' => 'Leather',
                'size' => 42,
            ],
            'title' => 'Red Shoe',
        ]);

        self::assertSame($first->snapshotHash, $second->snapshotHash);
        self::assertSame(['attributes', 'slug', 'title'], $first->fieldNames);
        self::assertSame('Red Shoe', $first->payload['title']);
    }

    #[Test]
    public function it_rejects_unnecessary_or_sensitive_input_fields_at_any_depth(): void
    {
        $minimizer = new SeoAiInputMinimizer;

        $this->expectException(InvalidArgumentException::class);

        $minimizer->minimize([
            'title' => 'Red Shoe',
            'attributes' => [
                'customer_name' => 'Private Customer',
            ],
        ]);
    }

    #[Test]
    public function it_rejects_unknown_root_fields_instead_of_silently_sending_them(): void
    {
        $minimizer = new SeoAiInputMinimizer;

        $this->expectException(InvalidArgumentException::class);

        $minimizer->minimize([
            'title' => 'Red Shoe',
            'orders' => [['id' => 1]],
        ]);
    }
}
