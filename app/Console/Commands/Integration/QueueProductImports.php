<?php

namespace App\Console\Commands\Integration;

use App\Jobs\MidOceanProductImportJob;
use App\Jobs\StrickerProductImportJob;
use App\Jobs\XDConnectProductImportJob;
use Illuminate\Console\Command;

class QueueProductImports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:queue-imports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queues all product import commands.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startTime = microtime(true);

        $this->info("Queuing MidOcean Import...\n");
        MidOceanProductImportJob::dispatch()->onQueue('import-jobs');

        $this->info("\nQueuing Stricker Import...\n");
        StrickerProductImportJob::dispatch()->onQueue('import-jobs');

        $this->info("\nQueuing XDConnect Import...\n");
        XDConnectProductImportJob::dispatch()->onQueue('import-jobs');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->info("\nAll product import commands have been queued successfully.");
        $this->line('<fg=yellow>Execution time: '.round($executionTime, 2).' seconds</>', 'info');

        return Command::SUCCESS;
    }
}
