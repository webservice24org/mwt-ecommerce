<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Seo\GenerativeSearch\Services\AiCrawlerPolicyService;
use App\Domain\Seo\GenerativeSearch\Services\AiCrawlerRobotsPolicyAppender;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AppendAiCrawlerPolicyToRobotsResponse
{
    public function __construct(
        private AiCrawlerPolicyService $policies,
        private AiCrawlerRobotsPolicyAppender $appender,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $response =
            $next(
                $request,
            );

        if (
            ! $request->is(
                'robots.txt',
            )
            || ! in_array(
                $request->method(),
                [
                    'GET',
                    'HEAD',
                ],
                true,
            )
            || $response->getStatusCode() !==
                200
        ) {
            return $response;
        }

        $content =
            $response
                ->getContent();

        if (! is_string($content)) {
            return $response;
        }

        $response->setContent(
            $this
                ->appender
                ->append(
                    $content,
                    $this
                        ->policies
                        ->get(),
                ),
        );

        $response->headers->remove(
            'Content-Length',
        );

        return $response;
    }
}
