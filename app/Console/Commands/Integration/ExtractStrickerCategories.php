<?php

namespace App\Console\Commands\Integration;

use Illuminate\Console\Command;

class ExtractStrickerCategories extends AbstractCategoryExtractCommand //Ja kādreiz nepieciešams, tad var arī importēt pārējo supplier kategorijas
{
    protected bool $isReady = false;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:extract-stricker-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */

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
