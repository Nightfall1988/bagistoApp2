<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('print_techniques', function (Blueprint $table) {
            $table->increments('id');
            $table->string('technique_id');
            $table->string('pricing_type');
            $table->string('setup');
            $table->string('description');
            $table->string('setup_repeat');
            $table->string('next_colour_cost_indicator');
            $table->string('position_id');
            $table->string('minimum_colors');
            $table->string('range_id');
            $table->string('area_from');
            $table->string('area_to');
            $table->longtext('pricing_data');
            $table->boolean('default');
            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_techniques');
    }
};
