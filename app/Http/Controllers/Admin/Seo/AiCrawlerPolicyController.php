<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\GenerativeSearch\Services\AiCrawlerPolicyService;
use App\Domain\Seo\GenerativeSearch\Services\AiCrawlerRobotsPolicyAppender;
use App\Http\Requests\Admin\Seo\UpdateAiCrawlerPolicyRequest;
use App\Models\AiCrawlerPolicyEvent;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class AiCrawlerPolicyController
{
    public function index(
        AiCrawlerPolicyService $policies,
        AiCrawlerRobotsPolicyAppender $appender,
    ): Response {
        $policy =
            $policies->get();

        $history =
            AiCrawlerPolicyEvent::query()
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(
                    static fn (
                        AiCrawlerPolicyEvent $event,
                    ): array => [
                        'id' => $event->id,

                        'version' => $event->version,

                        'before_policy' => $event->before_policy,

                        'after_policy' => $event->after_policy,

                        'changed_by_admin_id' => $event
                            ->changed_by_admin_id,

                        'created_at' => $event
                            ->created_at
                            ->toIso8601String(),
                    ],
                )
                ->values()
                ->all();

        return Inertia::render(
            'Admin/Seo/GenerativeSearch/CrawlerPolicy',
            [
                'policy' => $policy->toArray(),

                'robotsPreview' => $appender->append(
                    "User-agent: *\n"
                    ."Allow: /\n"
                    ."Disallow: /admin\n"
                    ."Disallow: /account\n"
                    ."\n"
                    ."# Existing 10.4D robots output continues above this managed block.\n",
                    $policy,
                ),

                'history' => $history,

                'platformNotes' => [
                'oai_search_bot' => 'OpenAI search crawler. Disallowing it opts the site out of automatic ChatGPT Search crawling; this is separate from GPTBot training control.',

                'gpt_bot' => 'OpenAI model-training crawler. This control is independent from OAI-SearchBot search visibility.',

                'google_extended' => 'Google-Extended is a control token for Gemini model-training and grounding uses. It does not control inclusion or ranking in Google Search.',

                'chatgpt_user' => 'ChatGPT-User is user-initiated rather than an automatic search crawler, so L.2 does not expose it as a robots policy switch.',

                'shared_training_token' => 'Google has announced future support for a shared AI-training policy token in 2027. L.2 does not emit that future token yet.',
                ],
            ],
        );
    }

    /**
     * @throws ValidationException
     */
    public function update(
        UpdateAiCrawlerPolicyRequest $request,
        AiCrawlerPolicyService $policies,
    ): RedirectResponse {
        try {
            $policies->save(
                allowOaiSearchBot: $request->boolean(
                    'allow_oai_search_bot',
                ),

                allowGptBot: $request->boolean(
                    'allow_gpt_bot',
                ),

                allowGoogleExtended: $request->boolean(
                    'allow_google_extended',
                ),

                expectedVersion: $request->integer(
                    'version',
                ),

                changedByAdminId: $this->actorId(
                    $request,
                ),
            );
        } catch (
            DomainException $exception
        ) {
            throw ValidationException::withMessages([
                'version' => $exception
                    ->getMessage(),
            ]);
        }

        return back()->with(
            'success',
            'AI crawler policy updated.',
        );
    }

    private function actorId(
        Request $request,
    ): ?int {
        $identifier =
            $request
                ->user()
                ?->getAuthIdentifier();

        if (
            is_int(
                $identifier,
            )
        ) {
            return $identifier;
        }

        if (
            is_string(
                $identifier,
            )
            && ctype_digit(
                $identifier,
            )
        ) {
            return (int) $identifier;
        }

        return null;
    }
}
