<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Seo\Merchant\Services\MerchantOrganizationPolicyStructuredDataResolver;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final readonly class ShareMerchantPolicyStructuredData
{
    public function __construct(
        private MerchantOrganizationPolicyStructuredDataResolver $resolver,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        if (
            ! $request->routeIs(
                'admin.*',
            )
        ) {
            Inertia::share(
                'merchantPolicyStructuredData',
                fn () => $this
                    ->resolver
                    ->resolve(),
            );
        }

        return $next(
            $request,
        );
    }
}
