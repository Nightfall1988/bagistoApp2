<?php

namespace App\Console\Commands;
use App\Services\PrintCalculatorImportService;

use Illuminate\Console\Command;

class PrintCalculatorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'print-calculator-import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Print Calculator data import';

    public function __construct(PrintCalculatorImportService $service)
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
        $this->service->importPrintData();
    }
}
