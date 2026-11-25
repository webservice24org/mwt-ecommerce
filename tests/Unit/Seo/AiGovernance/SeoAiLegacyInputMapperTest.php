<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\AiGovernance;

use App\Domain\Seo\AiGovernance\Support\SeoAiInputMinimizer;
use App\Domain\Seo\AiGovernance\Support\SeoAiLegacyInputMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SeoAiLegacyInputMapperTest extends TestCase
{
    public function test_it_maps_only_governed_content_fields_from_legacy_snapshot(): void
    {
        $mapped = (new SeoAiLegacyInputMapper)->map([
            'prompt_version' => 'ai-seo-metadata-v1',
            'contract_version' => 'ai-seo-metadata-v1',
            'entity' => [
                'type' => 'product',
                'id' => 42,
                'name' => 'USB Cable',
                'slug' => 'usb-cable',
                'description' => 'Braided USB cable.',
                'brand' => ['name' => 'MWT'],
                'categories' => [['name' => 'Cables']],
                'images' => [['alt' => 'USB Cable']],
                'url' => 'https://example.test/products/usb-cable',
            ],
            'seo' => [
                'title' => 'Existing title',
            ],
            'provider_instructions' => 'Legacy prompt internals must not be governed input.',
        ]);

        self::assertSame('product', $mapped['entity_type']);
        self::assertSame('42', $mapped['entity_key']);
        self::assertSame('USB Cable', $mapped['title']);
        self::assertSame('usb-cable', $mapped['slug']);
        self::assertSame('https://example.test/products/usb-cable', $mapped['public_url']);
        self::assertArrayNotHasKey('prompt_version', $mapped);
        self::assertArrayNotHasKey('contract_version', $mapped);
        self::assertArrayNotHasKey('provider_instructions', $mapped);

        $safe = (new SeoAiInputMinimizer)->minimize($mapped);

        self::assertEquals($mapped, $safe->payload);
        self::assertSame(64, strlen($safe->snapshotHash));
    }

    public function test_p1_minimizer_still_rejects_sensitive_nested_fields_after_mapping(): void
    {
        $mapped = (new SeoAiLegacyInputMapper)->map([
            'entity' => [
                'type' => 'product',
                'id' => 7,
                'name' => 'Sensitive Fixture',
                'attributes' => [
                    'payment_card_reference' => 'must-not-leave-app',
                ],
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);

        (new SeoAiInputMinimizer)->minimize($mapped);
    }
}
