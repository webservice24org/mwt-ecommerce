<?php

declare(strict_types=1);

namespace Tests\Feature\FooterBuilder;

use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\FooterConfigDefaults;
use App\Domain\FooterBuilder\Storefront\FooterStorefrontResolver;
use App\Models\FooterSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class FooterStorefrontSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('footerTemplates')]
    public function test_safe_internal_urls_are_allowed(
        FooterTemplate $template,
    ): void {
        $config =
            $this->defaults(
                $template,
            );

        $config['link_groups'] = [
            [
                'heading' => 'Company',

                'links' => [
                    [
                        'label' => 'About',

                        'url' => '/about',
                    ],
                    [
                        'label' => 'Contact',

                        'url' => '/contact?source=footer',
                    ],
                ],
            ],
        ];

        $resolved =
            $this->resolve(
                template: $template,
                config: $config,
            );

        $this->assertNotNull(
            $resolved,
        );

        $this->assertSame(
            '/about',
            $resolved[
                'config'
            ][
                'link_groups'
            ][0][
                'links'
            ][0][
                'url'
            ],
        );

        $this->assertSame(
            '/contact?source=footer',
            $resolved[
                'config'
            ][
                'link_groups'
            ][0][
                'links'
            ][1][
                'url'
            ],
        );
    }

    #[DataProvider('footerTemplates')]
    public function test_safe_https_urls_are_allowed(
        FooterTemplate $template,
    ): void {
        $config =
            $this->defaults(
                $template,
            );

        $config['social_links'] = [
            [
                'platform' => 'facebook',

                'url' => 'https://example.com/facebook',
            ],
        ];

        $resolved =
            $this->resolve(
                template: $template,
                config: $config,
            );

        $this->assertNotNull(
            $resolved,
        );

        $this->assertSame(
            'https://example.com/facebook',
            $resolved[
                'config'
            ][
                'social_links'
            ][0][
                'url'
            ],
        );
    }

    #[DataProvider('footerTemplates')]
    public function test_hash_urls_are_allowed(
        FooterTemplate $template,
    ): void {
        $config =
            $this->defaults(
                $template,
            );

        $config['developer'] = [
            'prefix' => 'Created by',

            'name' => 'MicroWeb',

            'url' => '#developer',
        ];

        $resolved =
            $this->resolve(
                template: $template,
                config: $config,
            );

        $this->assertNotNull(
            $resolved,
        );

        $this->assertSame(
            '#developer',
            $resolved[
                'config'
            ][
                'developer'
            ][
                'url'
            ],
        );
    }

    #[DataProvider('unsafeUrls')]
    public function test_unsafe_link_group_urls_are_never_exposed_to_the_storefront(
        string $unsafeUrl,
    ): void {
        $template =
            FooterTemplate::LuxeNewsletter;

        $config =
            $this->defaults(
                $template,
            );

        $config['link_groups'] = [
            [
                'heading' => 'Unsafe',

                'links' => [
                    [
                        'label' => 'Unsafe Link',

                        'url' => $unsafeUrl,
                    ],
                ],
            ],
        ];

        $resolved =
            $this->resolve(
                template: $template,
                config: $config,
            );

        /*
         * Invalid persisted footer configuration
         * must fail closed.
         *
         * The public storefront should receive no
         * footer rather than receive an unsafe URL.
         */
        $this->assertNull(
            $resolved,
        );
    }

    #[DataProvider('unsafeUrls')]
    public function test_unsafe_social_urls_are_never_exposed_to_the_storefront(
        string $unsafeUrl,
    ): void {
        $template =
            FooterTemplate::MarketplaceTrust;

        $config =
            $this->defaults(
                $template,
            );

        $config['social_links'] = [
            [
                'platform' => 'facebook',

                'url' => $unsafeUrl,
            ],
        ];

        $resolved =
            $this->resolve(
                template: $template,
                config: $config,
            );

        $this->assertNull(
            $resolved,
        );
    }

    #[DataProvider('unsafeUrls')]
    public function test_unsafe_developer_urls_are_never_exposed_to_the_storefront(
        string $unsafeUrl,
    ): void {
        $template =
            FooterTemplate::MinimalLocalized;

        $config =
            $this->defaults(
                $template,
            );

        $config['developer'] = [
            'prefix' => 'Created by',

            'name' => 'MicroWeb',

            'url' => $unsafeUrl,
        ];

        $resolved =
            $this->resolve(
                template: $template,
                config: $config,
            );

        $this->assertNull(
            $resolved,
        );
    }

    #[DataProvider('unsafeUrls')]
    public function test_unsafe_marketplace_promotion_urls_are_never_exposed_to_the_storefront(
        string $unsafeUrl,
    ): void {
        $template =
            FooterTemplate::MarketplaceTrust;

        $config =
            $this->defaults(
                $template,
            );

        $config[
            'promotion'
        ][
            'button_url'
        ] =
            $unsafeUrl;

        $resolved =
            $this->resolve(
                template: $template,
                config: $config,
            );

        $this->assertNull(
            $resolved,
        );
    }

    public function test_protocol_relative_urls_are_rejected(): void
    {
        $template =
            FooterTemplate::MarketplaceTrust;

        $config =
            $this->defaults(
                $template,
            );

        $config['popular_links'] = [
            [
                'label' => 'Unsafe',

                'url' => '//evil.example/path',
            ],
        ];

        $resolved =
            $this->resolve(
                template: $template,
                config: $config,
            );

        $this->assertNull(
            $resolved,
        );
    }

    public function test_invalid_stored_configuration_does_not_break_the_public_request(): void
    {
        $template =
            FooterTemplate::MarketplaceTrust;

        $config =
            $this->defaults(
                $template,
            );

        $config['developer'] = [
            'prefix' => 'Created by',

            'name' => 'Unsafe',

            'url' => 'javascript:alert(1)',
        ];

        FooterSetting::query()
            ->create([
                'singleton_key' => FooterSetting::SINGLETON_KEY,

                'template' => $template,

                'config' => $config,

                'is_enabled' => true,
            ]);

        $response =
            $this->get(
                route(
                    'home',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->where(
                        'storefrontFooter',
                        null,
                    ),
            );
    }

    /**
     * @return array<
     *     string,
     *     array{
     *         0: FooterTemplate
     *     }
     * >
     */
    public static function footerTemplates(): array
    {
        return [
            'luxe newsletter' => [
                FooterTemplate::LuxeNewsletter,
            ],

            'minimal localized' => [
                FooterTemplate::MinimalLocalized,
            ],

            'marketplace trust' => [
                FooterTemplate::MarketplaceTrust,
            ],
        ];
    }

    /**
     * @return array<
     *     string,
     *     array{
     *         0: string
     *     }
     * >
     */
    public static function unsafeUrls(): array
    {
        return [
            'javascript' => [
                'javascript:alert(1)',
            ],

            'javascript uppercase' => [
                'JAVASCRIPT:alert(1)',
            ],

            'data' => [
                'data:text/html,<script>alert(1)</script>',
            ],

            'protocol relative' => [
                '//evil.example/path',
            ],

            'ftp' => [
                'ftp://evil.example/file',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{
     *     template: string,
     *     config: array<string, mixed>
     * }|null
     */
    private function resolve(
        FooterTemplate $template,
        array $config,
    ): ?array {
        FooterSetting::query()
            ->create([
                'singleton_key' => FooterSetting::SINGLETON_KEY,

                'template' => $template,

                'config' => $config,

                'is_enabled' => true,
            ]);

        return $this->app
            ->make(
                FooterStorefrontResolver::class,
            )
            ->resolve();
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(
        FooterTemplate $template,
    ): array {
        return (
            new FooterConfigDefaults
        )->for(
            $template,
        );
    }
}
