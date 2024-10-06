<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class MidOceanProductImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Artisan::call('integration:midocean:import');

        $output = Artisan::output();

        Log::info('MidOceanProductImport Output: ' . $output);

        $this->outputToConsole($output);
    }

    /**
     * Output to the console, if you're running queue work in the terminal.
     */
    protected function outputToConsole(string $output)
    {
        echo $output;
    }
}
