<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currency_exchange_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('rate', 12, 6); // Adjusted precision and scale
            $table->unsignedInteger('target_currency'); // Changed to unsignedInteger
            $table->unique('target_currency'); // Ensures unique target currency
            $table->foreign('target_currency', 'fk_currency_exchange_rates_target_currency')
                  ->references('id')->on('currencies')
                  ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('currency_exchange_rates', function (Blueprint $table) {
            $table->dropForeign('fk_currency_exchange_rates_target_currency');
        });

        Schema::dropIfExists('currency_exchange_rates');
    }
};
