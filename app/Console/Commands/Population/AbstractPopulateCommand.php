<?php

namespace App\Console\Commands\Population;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;

abstract class AbstractPopulateCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $data = $this->getData();
        $transformedData = $this->transformData($data);
        $this->upsertData($transformedData);
    }

    abstract protected function getData(): Collection;

    abstract protected function transformData(Collection $data): Collection;

    abstract protected function upsertData(Collection $data): void;
}
