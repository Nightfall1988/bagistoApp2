<?php

namespace App\Console\Commands\Integration;

use Illuminate\Console\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class AbstractCategoryExtractCommand extends Command
{
    protected bool $isReady = true;

    public function handle(): void
    {
        if ($this->isReady) {
            $jsonData = $this->checkAndLoadDataFromCache();
            $transformedData = $this->transformData($jsonData);
            $this->insertTransformedData($transformedData);
            $this->output = new ConsoleOutput;
            $this->info($this->getSupplierName().' categories extracted successfully.');
        }
    }

    abstract protected function checkAndLoadDataFromCache(): mixed;

    abstract protected function transformData(mixed $jsonData): array;

    abstract protected function insertTransformedData(array $categories): void;

    abstract protected function getSupplierName(): string;
}
