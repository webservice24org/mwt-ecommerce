<?php

namespace App\Http\Middleware;

use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Queries\Storefront\StorefrontCategoryMenuQuery;
use App\Domain\FooterBuilder\Storefront\FooterStorefrontResolver;
use App\Domain\Wishlist\Services\WishlistService;
use App\Models\HeaderSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => function () use ($request): ?array {
                    $user = $request->user();

                    if (! $user instanceof User) {
                        return null;
                    }

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at,
                    ];
                },
            ],

            'flash' => [
                'success' => fn () => $request
                    ->session()
                    ->get('success'),

                'error' => fn () => $request
                    ->session()
                    ->get('error'),
            ],

            'storefrontCart' => function (): array {
                /** @var CartService $cart */
                $cart = app(
                    CartService::class,
                );

                return $cart
                    ->summary()
                    ->toArray();
            },

            'storefrontHeader' => function (): array {
                $header = HeaderSetting::singleton();

                return [
                    'template' => $header->template->value,
                    'config' => $header->resolvedConfig(),
                    'is_enabled' => $header->is_enabled,
                ];
            },

            'storefrontFooter' => static function () use ($request): ?array {
                /*
                 * The Footer Builder belongs to the public
                 * storefront. Do not perform the footer
                 * database lookup for Admin Inertia pages.
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

            'storefrontWishlist' => function (): array {
                /** @var WishlistService $wishlist */
                $wishlist = app(
                    WishlistService::class,
                );

                return [
                    'count' => $wishlist->count(),
                    'product_ids' => $wishlist->ids(),
                ];
            },

            'storefrontCategoryMenu' => fn (): array => app(
                StorefrontCategoryMenuQuery::class,
            )->get(),

        ];
    }
}
