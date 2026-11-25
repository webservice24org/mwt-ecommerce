<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\GenerativeSearch;

use App\Domain\Seo\GenerativeSearch\Enums\GenerativeSearchFindingSeverity;
use App\Domain\Seo\GenerativeSearch\Enums\GenerativeSearchReadinessStatus;
use App\Domain\Seo\GenerativeSearch\GenerativeSearchReadinessPolicy;
use PHPUnit\Framework\TestCase;

final class GenerativeSearchReadinessContractTest extends TestCase
{
    public function test_status_contract_is_non_numeric_and_explicit(): void
    {
        self::assertSame(
            [
                'ready',
                'needs_work',
                'blocked',
            ],
            array_map(
                static fn (
                    GenerativeSearchReadinessStatus $status,
                ): string => $status->value,
                GenerativeSearchReadinessStatus::cases(),
            ),
        );

        self::assertSame(
            [
                'blocker',
                'warning',
                'info',
            ],
            array_map(
                static fn (
                    GenerativeSearchFindingSeverity $severity,
                ): string => $severity->value,
                GenerativeSearchFindingSeverity::cases(),
            ),
        );
    }

    public function test_policy_is_versioned_and_supports_only_public_seo_entity_types(): void
    {
        self::assertSame(
            'generative-search-readiness-v1',
            GenerativeSearchReadinessPolicy::VERSION,
        );

        self::assertSame(
            [
                'product',
                'category',
                'brand',
                'page',
            ],
            GenerativeSearchReadinessPolicy::recognizedEntityTypes(),
        );
    }

    public function test_policy_does_not_invent_special_ai_schema_type(): void
    {
        foreach (
            [
                'product',
                'category',
                'brand',
                'page',
            ] as $type
        ) {
            foreach (
                GenerativeSearchReadinessPolicy::expectedStructuredDataTypes(
                    $type,
                ) as $schemaType
            ) {
                self::assertNotContains(
                    strtolower(
                        $schemaType,
                    ),
                    [
                        'ai',
                        'geo',
                        'generativeengineoptimization',
                        'aiengine',
                    ],
                );
            }
        }
    }
}
