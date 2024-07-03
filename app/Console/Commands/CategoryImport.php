<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CategoryImportService;

class CategoryImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'category-import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports and maps product categories';

    /**
     * Execute the console command.
     */

    public function __construct(CategoryImportService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle()
    {
        $this->service->importMidoceanData();
    }
}
