<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class StrickerProductImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function handle()
    {
        Artisan::call('integration:stricker:import');

        $output = Artisan::output();

        Log::info('StrickerProductImport Output: ' . $output);

        $this->outputToConsole($output);
    }

    protected function outputToConsole(string $output)
    {
        echo $output;
    }
}
