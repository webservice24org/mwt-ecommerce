<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\AiGovernance;

use App\Domain\Seo\AiGovernance\Services\SeoAiLegacyRequestGovernance;
use App\Domain\Seo\AiGovernance\Services\SeoAiLegacySuggestionGovernance;
use App\Models\Admin;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

final class SeoAiLegacyReviewPublicationGovernanceContractTest extends TestCase
{
    #[Test]
    public function request_bridge_exposes_human_review_and_publication_mirroring(): void
    {
        foreach (['approve', 'reject', 'markPublished'] as $methodName) {
            $method = new ReflectionMethod(
                SeoAiLegacyRequestGovernance::class,
                $methodName,
            );

            self::assertTrue($method->isPublic());

            $parameters = $method->getParameters();

            self::assertCount(2, $parameters);
            self::assertSame(
                AiSeoRequest::class,
                (string) $parameters[0]->getType(),
            );
            self::assertSame(
                Admin::class,
                (string) $parameters[1]->getType(),
            );
        }
    }

    #[Test]
    public function suggestion_bridge_exposes_the_same_governed_decision_boundary(): void
    {
        foreach (['approve', 'reject', 'markPublished'] as $methodName) {
            $method = new ReflectionMethod(
                SeoAiLegacySuggestionGovernance::class,
                $methodName,
            );

            self::assertTrue($method->isPublic());

            $parameters = $method->getParameters();

            self::assertCount(2, $parameters);
            self::assertSame(
                AiSeoSuggestion::class,
                (string) $parameters[0]->getType(),
            );
            self::assertSame(
                Admin::class,
                (string) $parameters[1]->getType(),
            );
        }
    }

    #[Test]
    public function bridge_does_not_auto_publish_during_approval(): void
    {
        $source = file_get_contents(
            base_path(
                'app/Domain/Seo/AiGovernance/Services/SeoAiLegacySuggestionGovernance.php',
            ),
        );

        self::assertIsString($source);

        $approveStart = strpos($source, 'public function approve(');
        $rejectStart = strpos($source, 'public function reject(');

        self::assertNotFalse($approveStart);
        self::assertNotFalse($rejectStart);

        $approveBlock = substr(
            $source,
            (int) $approveStart,
            (int) $rejectStart - (int) $approveStart,
        );

        self::assertStringContainsString(
            '$this->requests->approve(',
            $approveBlock,
        );
        self::assertStringNotContainsString(
            'markPublished(',
            $approveBlock,
        );
    }
}
