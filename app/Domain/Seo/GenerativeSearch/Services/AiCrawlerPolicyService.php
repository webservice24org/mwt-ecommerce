<?php

declare(strict_types=1);

namespace App\Domain\Seo\GenerativeSearch\Services;

use App\Domain\Seo\GenerativeSearch\Data\AiCrawlerPolicyData;
use App\Models\AiCrawlerPolicy;
use App\Models\AiCrawlerPolicyEvent;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class AiCrawlerPolicyService
{
    private const CACHE_KEY =
        'seo:generative-search:ai-crawler-policy:v1';

    public function get(): AiCrawlerPolicyData
    {
        /** @var AiCrawlerPolicyData */
        return Cache::rememberForever(
            self::CACHE_KEY,
            function (): AiCrawlerPolicyData {
                $policy =
                    $this->policy();

                return $this->toData(
                    $policy,
                );
            },
        );
    }

    public function save(
        bool $allowOaiSearchBot,
        bool $allowGptBot,
        bool $allowGoogleExtended,
        int $expectedVersion,
        ?int $changedByAdminId = null,
    ): AiCrawlerPolicyData {
        $saved =
            DB::transaction(
                function () use (
                    $allowOaiSearchBot,
                    $allowGptBot,
                    $allowGoogleExtended,
                    $expectedVersion,
                    $changedByAdminId,
                ): AiCrawlerPolicy {
                    $policy =
                        AiCrawlerPolicy::query()
                            ->where(
                                'scope_key',
                                'global',
                            )
                            ->lockForUpdate()
                            ->first();

                    if ($policy === null) {
                        $policy =
                            AiCrawlerPolicy::query()
                                ->create([
                                    'scope_key' => 'global',

                                    'allow_oai_search_bot' => true,

                                    'allow_gpt_bot' => true,

                                    'allow_google_extended' => true,

                                    'version' => 1,

                                    'updated_by_admin_id' => null,
                                ]);

                        $policy =
                            AiCrawlerPolicy::query()
                                ->lockForUpdate()
                                ->findOrFail(
                                    $policy->id,
                                );
                    }

                    if (
                        $policy->version !==
                        $expectedVersion
                    ) {
                        throw new DomainException(
                            'AI crawler policy changed since this page was loaded. Refresh and review the latest settings before saving.',
                        );
                    }

                    $before =
                        $this->policyValues(
                            $policy,
                        );

                    $after = [
                        'allow_oai_search_bot' => $allowOaiSearchBot,

                        'allow_gpt_bot' => $allowGptBot,

                        'allow_google_extended' => $allowGoogleExtended,
                    ];

                    if ($before === $after) {
                        return $policy;
                    }

                    $nextVersion =
                        $policy->version
                        + 1;

                    $policy->update([
                        ...$after,

                        'version' => $nextVersion,

                        'updated_by_admin_id' => $changedByAdminId,
                    ]);

                    AiCrawlerPolicyEvent::query()
                        ->create([
                            'ai_crawler_policy_id' => $policy->id,

                            'version' => $nextVersion,

                            'before_policy' => $before,

                            'after_policy' => $after,

                            'changed_by_admin_id' => $changedByAdminId,

                            'created_at' => now(),
                        ]);

                    return $policy
                        ->refresh();
                },
            );

        Cache::forget(
            self::CACHE_KEY,
        );

        return $this->toData(
            $saved,
        );
    }

    public function forget(): void
    {
        Cache::forget(
            self::CACHE_KEY,
        );
    }

    private function policy(): AiCrawlerPolicy
    {
        return AiCrawlerPolicy::query()
            ->firstOrCreate(
                [
                    'scope_key' => 'global',
                ],
                [
                    'allow_oai_search_bot' => true,

                    'allow_gpt_bot' => true,

                    'allow_google_extended' => true,

                    'version' => 1,

                    'updated_by_admin_id' => null,
                ],
            );
    }

    private function toData(
        AiCrawlerPolicy $policy,
    ): AiCrawlerPolicyData {
        return new AiCrawlerPolicyData(
            allowOaiSearchBot: $policy
                ->allow_oai_search_bot,

            allowGptBot: $policy
                ->allow_gpt_bot,

            allowGoogleExtended: $policy
                ->allow_google_extended,

            version: $policy
                ->version,
        );
    }

    /**
     * @return array{
     *     allow_oai_search_bot: bool,
     *     allow_gpt_bot: bool,
     *     allow_google_extended: bool
     * }
     */
    private function policyValues(
        AiCrawlerPolicy $policy,
    ): array {
        return [
            'allow_oai_search_bot' => $policy
                ->allow_oai_search_bot,

            'allow_gpt_bot' => $policy
                ->allow_gpt_bot,

            'allow_google_extended' => $policy
                ->allow_google_extended,
        ];
    }
}
