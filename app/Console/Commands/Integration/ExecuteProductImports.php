<?php

namespace App\Console\Commands\Integration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ExecuteProductImports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:import-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executes all product import commands.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startTime = microtime(true);

        $this->info("Running MidOcean Import...\n");
        Artisan::call('integration:midocean:import', [], $this->output);

        $this->info("\nRunning Stricker Import...\n");
        Artisan::call('integration:stricker:import', [], $this->output);

        $this->info("\nRunning XDConnect Import...\n");
        Artisan::call('integration:xdconnect:import', [], $this->output);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->info("\nAll product import commands have been executed successfully.");
        $this->line('<fg=yellow>Execution time: '.round($executionTime, 2).' seconds</>', 'info');

        return Command::SUCCESS;
    }
}
