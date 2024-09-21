<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\XDConnectsApiService;

class XDConnectsProductImport extends Command
{

    public function __construct(XDConnectsApiService $service)
    {
        parent::__construct();
        $this->service = $service;
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xdconnects-import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'XD Connects Product import';

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
