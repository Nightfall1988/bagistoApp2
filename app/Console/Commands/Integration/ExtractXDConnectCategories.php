<?php

namespace App\Console\Commands\Integration;

use Illuminate\Console\Command;

class ExtractXDConnectCategories extends AbstractCategoryExtractCommand //Ja kā
{
    protected bool $isReady = false;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:extract-x-d-connect-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected function checkAndLoadDataFromCache(): mixed
    {
        // TODO: Implement checkAndLoadDataFromCache() method.
    }

    protected function transformData(mixed $jsonData): array
    {
        // TODO: Implement transformData() method.
    }

    protected function insertTransformedData(array $data): void
    {
        // TODO: Implement upsertTransformedData() method.
    }

    protected function getSupplierName(): string
    {
        // TODO: Implement getSupplierName() method.
    }
}
