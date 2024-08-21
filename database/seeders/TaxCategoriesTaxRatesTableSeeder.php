<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class TaxCategoriesTaxRatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tax_categories_tax_rates')->insert([
            'id' => 1,
            'tax_category_id' => 1,
            'tax_rate_id' => 1,
        ]);
    }
}
