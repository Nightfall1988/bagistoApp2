<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('print_technique_variable_costs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('print_technique_id');
            $table->string('range_id');
            $table->decimal('area_from', 8, 2);
            $table->decimal('area_to', 8, 2);
            $table->longText('pricing_data')->nullable();
            $table->timestamps();

            $table->foreign('print_technique_id')
                ->references('technique_id')
                ->on('print_techniques')
                ->onDelete('cascade');

            $table->unique(['print_technique_id', 'range_id'], 'attribute_print_technique_range_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('print_technique_variable_costs');
    }
};
