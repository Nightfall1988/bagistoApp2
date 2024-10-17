<?php

namespace App\Console\Commands\Integration;

use Illuminate\Console\Command;

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

        $this->info('Running MidOcean Import...');
        passthru('php artisan integration:midocean:import');

        $this->info("\nRunning Stricker Import...");
        passthru('php artisan integration:stricker:import');

        $this->info("\nRunning XDConnect Import...");
        passthru('php artisan integration:xdconnect:import');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->info("\nAll product import commands have been executed successfully.");
        $this->line('<fg=yellow>Execution time: '.round($executionTime, 2).' seconds</>', 'info');

        passthru('php artisan lv:duplicate-populate');

        $this->info("\nRunning image download...");
        passthru('php artisan app:download-and-upsert-product-images');

        return Command::SUCCESS;
    }
}
