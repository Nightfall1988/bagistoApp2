<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class XDConnectProductImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Artisan::call('integration:xdconnect:import');

        $output = Artisan::output();

        Log::info('StrickerProductImport Output: ' . $output);

        $this->outputToConsole($output);
    }

    protected function outputToConsole(string $output)
    {
        echo $output;
    }
}
