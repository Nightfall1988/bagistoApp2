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
        Schema::create('country_states', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('country_id')->nullable(); // Ensured unsigned and nullable
            $table->string('country_code')->nullable();
            $table->string('code')->nullable(); // Consider adding unique constraint if necessary
            $table->string('default_name')->nullable();

            // Foreign key constraint
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null'); // Changed to set null

            // Optional indexes
            $table->index('country_code'); // Index on country_code if frequently queried
            $table->index('code'); // Index on code if frequently queried
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('country_states');
    }
};
