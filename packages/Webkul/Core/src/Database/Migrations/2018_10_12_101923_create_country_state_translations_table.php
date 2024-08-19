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
        Schema::create('country_state_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('country_state_id'); // Ensured unsigned
            $table->string('locale');
            $table->text('default_name')->nullable();

            // Unique constraint on the combination of country_state_id and locale
            $table->unique(['country_state_id', 'locale']);

            // Foreign key constraint
            $table->foreign('country_state_id')->references('id')->on('country_states')->onDelete('cascade');

            // Optional index on locale
            $table->index('locale');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('country_state_translations');
    }
};
