<?php
namespace Hitexis\Marketing\Providers;

use Illuminate\Support\ServiceProvider;
use Hitexis\Marketing\Contracts\SearchTerm as SearchTermContract;
use Hitexis\Marketing\Models\SearchTerm;

class MarketingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(SearchTermContract::class, SearchTerm::class);
    }
}
