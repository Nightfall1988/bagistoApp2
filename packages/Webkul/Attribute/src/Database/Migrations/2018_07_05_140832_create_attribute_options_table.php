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
        Schema::create('attribute_options', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('attribute_id'); // Changed to unsignedInteger
            $table->string('admin_name')->nullable();
            $table->integer('sort_order')->nullable();
            $table->string('swatch_value')->nullable();

            $table->foreign('attribute_id', 'fk_attribute_options_attribute_id')
                  ->references('id')->on('attributes')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attribute_options', function (Blueprint $table) {
            $table->dropForeign('fk_attribute_options_attribute_id');
        });

        Schema::dropIfExists('attribute_options');
    }
};
