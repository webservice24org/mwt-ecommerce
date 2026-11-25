<?php

declare(strict_types=1);

namespace App\Jobs\Seo;

use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkItemExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class AiSeoBulkItemExecutionJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;

    public int $uniqueFor;

    public function __construct(
        public readonly int $itemId,
    ) {
        $this->tries =
            max(
                1,
                (int) config(
                    'ai-seo-bulk.max_attempts',
                    3,
                ),
            );

        $this->uniqueFor =
            max(
                60,
                (int) config(
                    'ai-seo-bulk.unique_for_seconds',
                    3600,
                ),
            );
    }

    public function uniqueId(): string
    {
        return 'ai-seo-bulk-item:'
            .$this->itemId;
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        $configured =
            config(
                'ai-seo-bulk.backoff_seconds',
                [
                    30,
                    120,
                    300,
                ],
            );

        if (! is_array($configured)) {
            return [
                30,
                120,
                300,
            ];
        }

        $values = [];

        foreach ($configured as $value) {
            $seconds =
                (int) $value;

            if ($seconds > 0) {
                $values[] =
                    $seconds;
            }
        }

        return $values === []
            ? [
                30,
                120,
                300,
            ]
            : $values;
    }

    /**
     * @throws Throwable
     */
    public function handle(
        AiSeoBulkItemExecutionService $execution,
    ): void {
        try {
            $execution->execute(
                $this->itemId,
            );
        } catch (
            Throwable $exception
        ) {
            if (
                $this->attempts() <
                $this->tries
            ) {
                $execution
                    ->markRetryQueued(
                        $this->itemId,
                        $exception,
                    );
            } else {
                $execution
                    ->markTerminalFailure(
                        $this->itemId,
                        $exception,
                    );
            }

            throw $exception;
        }
    }

    public function failed(
        ?Throwable $exception,
    ): void {
        if ($exception === null) {
            return;
        }

        app(
            AiSeoBulkItemExecutionService::class,
        )->markTerminalFailure(
            $this->itemId,
            $exception,
        );
    }
}
