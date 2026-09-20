<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

trait MeasuresDatabaseQueries
{
    /**
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return array{
     *     result: TResult,
     *     count: int
     * }
     */
    protected function measureDatabaseQueries(
        callable $callback,
    ): array {
        $connection = DB::connection();

        $connection->flushQueryLog();
        $connection->enableQueryLog();

        try {
            $result = $callback();

            return [
                'result' => $result,
                'count' => count(
                    $connection->getQueryLog(),
                ),
            ];
        } finally {
            $connection->disableQueryLog();
            $connection->flushQueryLog();
        }
    }
}
