<?php

namespace App\Http\Middleware;

use App\Domain\Cart\Services\CartPromoPricingService;
use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Queries\Storefront\StorefrontCategoryMenuQuery;
use App\Domain\FooterBuilder\Storefront\FooterStorefrontResolver;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Domain\Seo\StructuredData\Services\GlobalStructuredDataResolver;
use App\Domain\Wishlist\Services\WishlistService;
use App\Domain\WebsiteSettings\AdminBrandingSchema;
use App\Models\CustomerAvatar;
use App\Models\HeaderSetting;
use App\Models\ThemeSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded
     * on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(
        Request $request,
    ): ?string {
        return parent::version(
            $request,
        );
    }

    /**
     * Define the props that are shared
     * by default.
     *
     * @return array<string, mixed>
     */
    public function share(
        Request $request,
    ): array {
        return [
            ...parent::share(
                $request,
            ),

            'auth' => [
                'user' => function () use ($request): ?array {
                    $user =
                        $request->user();

                    if (
                        ! $user instanceof User
                    ) {
                        return null;
                    }

                    return [
                        'id' => $user->id,

                        'name' => $user->name,

                        'email' => $user->email,

                        'email_verified_at' => $user
                            ->email_verified_at,
                    ];
                },
            ],

            'customerAvatar' => static function () use ($request): ?string {
                if (! $request->routeIs('customer.*', 'profile.edit')) {
                    return null;
                }

                $user = $request->user();
                if (! $user instanceof User || ! $user->isActive() || $user->isAnonymized()) {
                    return null;
                }

                $version = CustomerAvatar::query()->whereKey($user->id)->value('version');

                return is_string($version)
                    ? route('customer.avatar.show', ['v' => $version], false)
                    : null;
            },

            'flash' => [
                'success' => fn () => $request
                    ->session()
                    ->get(
                        'success',
                    ),

                'error' => fn () => $request
                    ->session()
                    ->get(
                        'error',
                    ),
            ],

            'storefrontCart' => function () use ($request): array {
                /** @var CartService $cart */
                $cart = app(
                    CartService::class,
                );

                /** @var CartPromoPricingService $pricing */
                $pricing = app(
                    CartPromoPricingService::class,
                );

                $user =
                    $request->user();

                $userId =
                    $user instanceof User
                    ? $user->id
                    : null;

                return $pricing
                    ->calculate(
                        cart: $cart->summary(),

                        userId: $userId,
                    )
                    ->toArray();
            },

            'storefrontHeader' => function (): array {
                $header =
                    HeaderSetting::singleton();

                return [
                    'template' => $header
                        ->template
                        ->value,

                    'config' => $header
                        ->resolvedConfig(),

                    'is_enabled' => $header
                        ->is_enabled,
                ];
            },

            'adminBranding' => static fn (): ?array => $request->routeIs('admin.*')
                ? AdminBrandingSchema::saved()
                : null,

            'storefrontTheme' => static fn (): ?array => $request->routeIs('admin.*')
                ? null
                : ['product_cards' => ThemeSetting::productCardStyles()],

            'storefrontFooter' => static function () use ($request): ?array {
                /*
                 * The Footer Builder belongs
                 * to the public storefront.
                 *
                 * Do not perform the footer
                 * database lookup for Admin
                 * Inertia pages.
                 */
                if (
                    $request->routeIs(
                        'admin.*',
                    )
                ) {
                    return null;
                }

                return app(
                    FooterStorefrontResolver::class,
                )->resolve();
            },

            'storefrontWishlist' => function () use ($request): array {
                $wishlist =
                    app(
                        WishlistService::class,
                    );

                $authenticated =
                    $request->user();

                $user =
                    $authenticated instanceof User
                    ? $authenticated
                    : null;

                /*
                 * If the customer has just authenticated,
                 * merge any pre-login guest wishlist before
                 * exposing authoritative state.
                 */
                if ($user !== null) {
                    $wishlist->mergeGuestInto(
                        $user,
                    );
                }

                $productIds =
                    $wishlist->ids(
                        user: $user,
                    );

                return [
                    'count' => count(
                        $productIds,
                    ),

                    'product_ids' => $productIds,
                ];
            },

            'storefrontStructuredData' => function () use ($request): ?array {
                /*
                 * Global Organization/WebSite structured data
                 * belongs only to the public storefront.
                 */
                if (
                    $request->routeIs(
                        'admin.*',
                    )
                ) {
                    return null;
                }

                $settings =
                    app(
                        SeoSettingsService::class,
                    )->get();

                return app(
                    GlobalStructuredDataResolver::class,
                )
                    ->resolve(
                        $settings,
                    )
                    ?->toArray();
            },

            'storefrontCategoryMenu' => fn (): array => app(
                StorefrontCategoryMenuQuery::class,
            )->get(),

            'storefrontSocialSeo' => static function () use ($request): ?array {
                /*
                 * Social metadata settings only belong to the
                 * public storefront. Do not query/share them
                 * for Admin Inertia pages.
                 */
                if (
                    $request->routeIs(
                        'admin.*',
                    )
                ) {
                    return null;
                }

                $settings =
                    app(
                        SeoSettingsService::class,
                    )->get();

                return [

                    'site_name' => $settings->siteName,
                    'default_og_image' => $settings->defaultOgImage,

                    'enable_open_graph' => $settings->enableOpenGraph,

                    'enable_twitter_cards' => $settings->enableTwitterCards,

                    'twitter_card' => $settings->twitterCard,

                ];
            },

        ];
    }
}
