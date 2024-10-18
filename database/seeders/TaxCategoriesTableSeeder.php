<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class TaxCategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tax_categories')->insert([
            'id' => 1,
            'name' => 'VAT',
            'code' => 'vat',
            'description' => 'VAT',
        ]);
    }
}
