<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai;

use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use PHPUnit\Framework\TestCase;

final class AiSeoFoundationSourceContractTest extends TestCase
{
    public function test_ai_seo_is_disabled_by_default_and_credentials_are_env_backed(): void
    {
        $source =
            $this->read(
                'config/ai-seo.php',
            );

        self::assertStringContainsString(
            "'AI_SEO_ENABLED'",
            $source,
        );

        self::assertStringContainsString(
            'false',
            $source,
        );

        self::assertStringContainsString(
            "'OPENAI_API_KEY'",
            $source,
        );

        self::assertStringNotContainsString(
            'sk-',
            $source,
        );
    }

    public function test_provider_uses_responses_structured_output_and_disables_storage(): void
    {
        $source =
            $this->read(
                'app/Domain/Seo/Ai/Providers/OpenAiResponsesSeoProvider.php',
            );

        foreach (
            [
                ".'/responses'",
                "'store' =>",
                'false',
                "'json_schema'",
                "'strict' =>",
                "'additionalProperties' =>",
            ] as $needle
        ) {
            self::assertStringContainsString(
                $needle,
                $source,
            );
        }
    }

    public function test_generation_service_cannot_directly_write_catalog_or_page_models(): void
    {
        $source =
            $this->read(
                'app/Domain/Seo/Ai/Services/AiSeoSuggestionGenerationService.php',
            );

        foreach (
            [
                'App\\Models\\Product',
                'App\\Models\\Category',
                'App\\Models\\Brand',
                'App\\Models\\Page',
                'seoMeta()',
                'meta_title',
                'meta_description',
                'canonical_url',
                'robots_index',
                'og_image',
                'twitter_image',
            ] as $forbidden
        ) {
            self::assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }

        self::assertStringContainsString(
            'AiSeoSuggestionValidator',
            $source,
        );

        self::assertStringContainsString(
            'AiSeoRequestLifecycleManager',
            $source,
        );
    }

    public function test_allowlist_enum_contains_only_reviewable_text_metadata_fields(): void
    {
        $fields =
            array_map(
                static fn (
                    AiSeoSuggestionField $field,
                ): string => $field->value,
                AiSeoSuggestionField::cases(),
            );

        self::assertSame(
            [
                'meta_title',
                'meta_description',
                'og_title',
                'og_description',
                'twitter_title',
                'twitter_description',
            ],
            $fields,
        );

        foreach (
            [
                'canonical_url',
                'robots_index',
                'robots_follow',
                'og_image',
                'twitter_image',
                'structured_data',
                'price',
                'stock_quantity',
                'status',
            ] as $forbidden
        ) {
            self::assertNotContains(
                $forbidden,
                $fields,
            );
        }
    }

    private function read(
        string $relativePath,
    ): string {
        $path =
            dirname(
                __DIR__,
                4,
            )
            .'/'
            .$relativePath;

        self::assertFileExists(
            $path,
        );

        $source =
            file_get_contents(
                $path,
            );

        self::assertIsString(
            $source,
        );

        return $source;
    }
}
