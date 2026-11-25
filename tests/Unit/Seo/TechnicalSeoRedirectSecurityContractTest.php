<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class TechnicalSeoRedirectSecurityContractTest extends TestCase
{
    public function test_slug_change_redirect_path_has_a_permanent_redirect_contract(): void
    {
        $sources = $this->phpSources([
            'app',
            'routes',
        ]);

        $matching = [];

        foreach ($sources as $path => $source) {
            $lower = strtolower($source);

            $mentionsSlug = str_contains($lower, 'slug');
            $mentionsRedirect = str_contains($lower, 'redirect');
            $mentionsPermanentStatus =
                str_contains($source, '301')
                || str_contains($source, 'HTTP_MOVED_PERMANENTLY')
                || str_contains($source, 'MOVED_PERMANENTLY');

            if ($mentionsSlug && $mentionsRedirect && $mentionsPermanentStatus) {
                $matching[$path] = $source;
            }
        }

        self::assertNotSame(
            [],
            $matching,
            'SEO slug-change handling must retain a permanent (301) redirect path.',
        );
    }

    public function test_seo_slug_redirect_sources_do_not_use_unvalidated_external_away_redirects(): void
    {
        $sources = $this->phpSources([
            'app',
            'routes',
        ]);

        foreach ($sources as $path => $source) {
            $lower = strtolower($source);

            if (! str_contains($lower, 'slug') || ! str_contains($lower, 'redirect')) {
                continue;
            }

            self::assertStringNotContainsString(
                'redirect()->away(',
                $source,
                $path.' must not implement SEO slug redirects as arbitrary external redirects.',
            );

            self::assertStringNotContainsString(
                'Redirect::away(',
                $source,
                $path.' must not implement SEO slug redirects as arbitrary external redirects.',
            );
        }
    }

    /**
     * @param  list<string>  $relativeRoots
     * @return array<string, string>
     */
    private function phpSources(array $relativeRoots): array
    {
        $projectRoot = dirname(__DIR__, 3);
        $sources = [];

        foreach ($relativeRoots as $relativeRoot) {
            $root = $projectRoot.'/'.$relativeRoot;

            if (! is_dir($root)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $root,
                    RecursiveDirectoryIterator::SKIP_DOTS,
                ),
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $path = $file->getPathname();
                $source = file_get_contents($path);

                if (! is_string($source)) {
                    continue;
                }

                $sources[$path] = $source;
            }
        }

        return $sources;
    }
}
