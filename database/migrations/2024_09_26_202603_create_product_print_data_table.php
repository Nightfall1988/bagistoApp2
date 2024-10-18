<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_print_data', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedBigInteger('print_manipulation_id')->nullable();
            $table->string('print_template');
            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('print_manipulation_id')
                ->references('id')
                ->on('print_manipulations')
                ->onDelete('set null');

            $table->unique(['product_id', 'print_template'], 'attribute_product_id_print_template_unique');
        });

    }

    public function down()
    {
        Schema::dropIfExists('product_print_data');
    }
};
