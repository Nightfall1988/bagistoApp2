<?php
namespace App\Repositories\Integration;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class BaseImportRepository
{
    protected int $upsertBatchSize;

    public function __construct()
    {
        $this->upsertBatchSize = env('UPSERT_BATCH_SIZE', 100);
    }

    protected function handleUpsert(callable $transactionCallback, int $maxRetries = 5, int $retryDelaySeconds = 1): void
    {
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            try {
                DB::transaction($transactionCallback);
                break;
            } catch (QueryException $exception) {
                if ($this->isDeadlockException($exception)) {
                    $retryCount++;
                    if ($retryCount >= $maxRetries) {
                        throw $exception;
                    }
                    usleep($retryDelaySeconds * 1000000);
                } else {
                    throw $exception;
                }
            }
        }
    }

    protected function isDeadlockException(QueryException $exception): bool
    {
        $deadlockCodes = ['40001', '40P01']; // SQL state codes for deadlocks in MySQL and PostgreSQL

        return in_array($exception->getCode(), $deadlockCodes);
    }
}
