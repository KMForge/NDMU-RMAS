<?php

namespace App\Support;

use Illuminate\Database\Events\QueryExecuted;

class SlowRequestMetrics
{
    private bool $active = false;

    private int $queryCount = 0;

    private float $databaseMs = 0.0;

    public function start(): void
    {
        $this->active = true;
        $this->queryCount = 0;
        $this->databaseMs = 0.0;
    }

    public function record(QueryExecuted $query): void
    {
        if (! $this->active) {
            return;
        }

        $this->queryCount++;
        $this->databaseMs += $query->time;
    }

    /** @return array{query_count: int, database_ms: float} */
    public function finish(): array
    {
        $this->active = false;

        return [
            'query_count' => $this->queryCount,
            'database_ms' => $this->databaseMs,
        ];
    }
}
