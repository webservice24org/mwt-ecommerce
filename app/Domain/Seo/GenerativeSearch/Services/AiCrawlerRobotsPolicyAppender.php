<?php

declare(strict_types=1);

namespace App\Domain\Seo\GenerativeSearch\Services;

use App\Domain\Seo\GenerativeSearch\Data\AiCrawlerPolicyData;

final readonly class AiCrawlerRobotsPolicyAppender
{
    private const BEGIN =
        '# BEGIN MWT AI CRAWLER POLICY';

    private const END =
        '# END MWT AI CRAWLER POLICY';

    public function append(
        string $robots,
        AiCrawlerPolicyData $policy,
    ): string {
        $robots =
            $this->withoutManagedBlock(
                $robots,
            );

        $lines = [
            self::BEGIN,

            '# OAI-SearchBot: '
            .(
                $policy->allowOaiSearchBot
                    ? 'allowed; inherits the existing generic robots rules'
                    : 'disallowed for automatic ChatGPT Search crawling'
            ),

            '# GPTBot: '
            .(
                $policy->allowGptBot
                    ? 'allowed; inherits the existing generic robots rules'
                    : 'disallowed for OpenAI model-training crawling'
            ),

            '# Google-Extended: '
            .(
                $policy->allowGoogleExtended
                    ? 'allowed; inherits the existing generic robots rules'
                    : 'disallowed for Google-Extended controlled uses'
            ),
        ];

        if (! $policy->allowOaiSearchBot) {
            $lines = [
                ...$lines,
                '',
                'User-agent: OAI-SearchBot',
                'Disallow: /',
            ];
        }

        if (! $policy->allowGptBot) {
            $lines = [
                ...$lines,
                '',
                'User-agent: GPTBot',
                'Disallow: /',
            ];
        }

        if (! $policy->allowGoogleExtended) {
            $lines = [
                ...$lines,
                '',
                'User-agent: Google-Extended',
                'Disallow: /',
            ];
        }

        $lines[] =
            self::END;

        $base =
            rtrim(
                $robots,
                "\r\n",
            );

        return (
            $base !== ''
                ? $base."\n\n"
                : ''
        )
        .implode(
            "\n",
            $lines,
        )
        ."\n";
    }

    private function withoutManagedBlock(
        string $robots,
    ): string {
        $pattern =
            '/(?:\r?\n)?'
            .preg_quote(
                self::BEGIN,
                '/',
            )
            .'.*?'
            .preg_quote(
                self::END,
                '/',
            )
            .'(?:\r?\n)?/s';

        return preg_replace(
            $pattern,
            "\n",
            $robots,
        )
        ?? $robots;
    }
}
