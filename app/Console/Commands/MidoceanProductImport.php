<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MidoceanApiService;

class MidoceanProductImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'midocean-import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Midoecan Product import';

    public function __construct(MidoceanApiService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
       $this->service->getData();
    }
}
