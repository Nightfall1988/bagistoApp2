<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StrickerApiService;

class StrickerProductImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stricker-import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stricker Product import';

    public function __construct(StrickerApiService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        ini_set('memory_limit', '512M');
        $this->service->setOutput($this->output);
        $this->service->getData();
    }
}
