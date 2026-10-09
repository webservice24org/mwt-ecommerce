<?php

namespace App\Http\Middleware;

use App\Domain\FooterBuilder\Storefront\FooterStorefrontResolver;
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
                'user' => $request->user(),
            ],

            'flash' => [
                'success' => fn () => $request
                    ->session()
                    ->get('success'),

                'error' => fn () => $request
                    ->session()
                    ->get('error'),
            ],

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
        ];
    }
}
