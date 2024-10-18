<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('printing_positions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_print_data_id');
            $table->string('position_id');
            $table->string('print_size_unit');
            $table->decimal('max_print_size_height', 8, 2);
            $table->decimal('max_print_size_width', 8, 2);
            $table->decimal('rotation', 8, 2);
            $table->string('print_position_type');
            $table->timestamps();

            $table->foreign('product_print_data_id')
                ->references('id')
                ->on('product_print_data')
                ->onDelete('cascade');

            // Unique constraint to ensure uniqueness on product_print_data_id and position_id
            $table->unique(['product_print_data_id', 'position_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('printing_positions');
    }
};
