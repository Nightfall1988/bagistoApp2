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
        Schema::create('attribute_option_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('attribute_option_id'); // Changed to unsignedInteger
            $table->string('locale');
            $table->text('label')->nullable();

            // Adding a unique index for 'attribute_option_id' and 'locale'
            $table->unique(['attribute_option_id', 'locale']);

            // Explicitly naming the foreign key constraint
            $table->foreign('attribute_option_id', 'fk_attribute_option_translations_option_id')
                  ->references('id')->on('attribute_options')
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
        // Drop the foreign key before dropping the table
        Schema::table('attribute_option_translations', function (Blueprint $table) {
            $table->dropForeign('fk_attribute_option_translations_option_id');
        });

        Schema::dropIfExists('attribute_option_translations');
    }
};
