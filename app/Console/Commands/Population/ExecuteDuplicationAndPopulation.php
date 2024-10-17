<?php

namespace App\Console\Commands\Population;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ExecuteDuplicationAndPopulation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lv:duplicate-populate';

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

        $this->info("\nRunning Product Super Attribute Population...\n");
        Artisan::call('populate:product-super-attributes', [], $this->output);

        $this->info("\nRunning Product Attribute Value Duplication...\n");
        Artisan::call('duplicate:product-attribute_values-lv', [], $this->output);

        $this->info("\nRunning Product Flat Duplication...\n");
        Artisan::call('duplicate:product-flat-lv', [], $this->output);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->info("\nAll duplicaiton and population commands have been executed successfully.");
        $this->line('<fg=yellow>Execution time: '.round($executionTime, 2).' seconds</>', 'info');

        return Command::SUCCESS;
    }
}
