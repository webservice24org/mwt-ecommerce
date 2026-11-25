<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\GenerativeSearch;

use App\Domain\Seo\GenerativeSearch\Data\AiCrawlerPolicyData;
use App\Domain\Seo\GenerativeSearch\Services\AiCrawlerRobotsPolicyAppender;
use PHPUnit\Framework\TestCase;

final class AiCrawlerRobotsPolicyAppenderTest extends TestCase
{
    public function test_allowed_crawlers_inherit_generic_rules_without_specific_allow_groups(): void
    {
        $robots =
            "User-agent: *\n"
            ."Allow: /\n"
            ."Disallow: /admin\n"
            ."Sitemap: https://example.com/sitemap.xml\n";

        $rendered =
            (new AiCrawlerRobotsPolicyAppender)
                ->append(
                    $robots,
                    $this->policy(
                        oai: true,

                        gpt: true,

                        google: true,
                    ),
                );

        self::assertStringContainsString(
            $robots,
            $rendered,
        );

        self::assertStringContainsString(
            '# BEGIN MWT AI CRAWLER POLICY',
            $rendered,
        );

        self::assertStringNotContainsString(
            "User-agent: OAI-SearchBot\nAllow: /",
            $rendered,
        );

        self::assertStringNotContainsString(
            "User-agent: GPTBot\nAllow: /",
            $rendered,
        );

        self::assertStringNotContainsString(
            "User-agent: Google-Extended\nAllow: /",
            $rendered,
        );
    }

    public function test_disabled_crawlers_get_only_specific_full_site_disallow_groups(): void
    {
        $rendered =
            (new AiCrawlerRobotsPolicyAppender)
                ->append(
                    "User-agent: *\n"
                    ."Disallow: /admin\n",
                    $this->policy(
                        oai: false,

                        gpt: true,

                        google: false,
                    ),
                );

        self::assertStringContainsString(
            "User-agent: OAI-SearchBot\nDisallow: /",
            $rendered,
        );

        self::assertStringNotContainsString(
            "User-agent: GPTBot\nDisallow: /",
            $rendered,
        );

        self::assertStringContainsString(
            "User-agent: Google-Extended\nDisallow: /",
            $rendered,
        );

        self::assertStringContainsString(
            "User-agent: *\nDisallow: /admin",
            $rendered,
        );
    }

    public function test_managed_block_is_replaced_idempotently(): void
    {
        $appender =
            new AiCrawlerRobotsPolicyAppender;

        $first =
            $appender->append(
                "User-agent: *\nAllow: /\n",
                $this->policy(
                    oai: false,

                    gpt: false,

                    google: false,
                ),
            );

        $second =
            $appender->append(
                $first,
                $this->policy(
                    oai: true,

                    gpt: false,

                    google: true,
                ),
            );

        self::assertSame(
            1,
            substr_count(
                $second,
                '# BEGIN MWT AI CRAWLER POLICY',
            ),
        );

        self::assertSame(
            1,
            substr_count(
                $second,
                '# END MWT AI CRAWLER POLICY',
            ),
        );

        self::assertStringNotContainsString(
            "User-agent: OAI-SearchBot\nDisallow: /",
            $second,
        );

        self::assertStringContainsString(
            "User-agent: GPTBot\nDisallow: /",
            $second,
        );
    }

    public function test_l2_does_not_manage_user_initiated_chatgpt_user_or_future_shared_token(): void
    {
        $source =
            file_get_contents(
                dirname(
                    __DIR__,
                    4,
                )
                .'/app/Domain/Seo/GenerativeSearch/Services/AiCrawlerRobotsPolicyAppender.php',
            );

        self::assertIsString(
            $source,
        );

        self::assertStringNotContainsString(
            'ChatGPT-User',
            $source,
        );

        self::assertStringNotContainsString(
            '__usage_ai-training',
            $source,
        );
    }

    private function policy(
        bool $oai,
        bool $gpt,
        bool $google,
    ): AiCrawlerPolicyData {
        return new AiCrawlerPolicyData(
            allowOaiSearchBot: $oai,

            allowGptBot: $gpt,

            allowGoogleExtended: $google,

            version: 1,
        );
    }
}
