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
        Schema::create('attribute_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('attribute_id');
            $table->string('locale');
            $table->text('name')->nullable();
            $table->unique(['attribute_id', 'locale']);
            
            // Naming the foreign key explicitly to avoid conflicts
            $table->foreign('attribute_id', 'fk_attribute_id')
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
        // Drop the foreign key first if it exists
        Schema::table('attribute_translations', function (Blueprint $table) {
            $table->dropForeign(['attribute_id']);
        });

        Schema::dropIfExists('attribute_translations');
    }
};
