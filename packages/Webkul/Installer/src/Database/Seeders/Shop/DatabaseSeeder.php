<?php

namespace Webkul\Installer\Database\Seeders\Shop;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        $this->call(ThemeCustomizationTableSeeder::class, false, ['parameters' => $parameters]);
        $this->call(ThemeCustomizationTableSeederEdit::class, false, ['parameters' => $parameters]);
    }
}
