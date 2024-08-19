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
        Schema::create('channel_inventory_sources', function (Blueprint $table) {
            $table->unsignedInteger('channel_id'); // Changed to unsignedInteger
            $table->unsignedInteger('inventory_source_id'); // Changed to unsignedInteger

            // Unique constraint to prevent duplicate entries
            $table->unique(['channel_id', 'inventory_source_id']);

            // Foreign key constraints
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            $table->foreign('inventory_source_id')->references('id')->on('inventory_sources')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channel_inventory_sources');
    }
};
