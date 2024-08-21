<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class TaxRatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tax_rates')->insert([
            'id' => 1,
            'identifier' => 'vat',
            'is_zip' => false,
            'zip_code' => '1',
            'zip_from' => null,
            'zip_to' => null,
            'state' => '*',
            'country' => 'LV',
            'tax_rate' => 21.1,
        ]);
    }
}
