<?php

namespace Webkul\Installer\Database\Seeders\Inventory;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventorySourceTableSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        DB::table('inventory_sources')->delete();

        $defaultLocale = $parameters['default_locale'] ?? config('app.locale');

        DB::table('inventory_sources')->insert([
            'id'             => 1,
            'code'           => 'default',
            'name'           => trans('installer::app.seeders.inventory.inventory-sources.name', [], $defaultLocale),
            'contact_name'   => trans('installer::app.seeders.inventory.inventory-sources.name', [], $defaultLocale),
            'contact_email'  => 'info@logoprint.lv',
            'contact_number' => +37126383899,
            'status'         => 1,
            'country'        => 'LV',
            'state'          => 'Rīga',
            'street'         => 'Dzirnavu iela 57a-4',
            'city'           => 'Rīga',
            'postcode'       => '1010',
        ]);
    }
}
