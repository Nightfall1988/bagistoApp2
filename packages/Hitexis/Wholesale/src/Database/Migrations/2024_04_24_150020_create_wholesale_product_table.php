<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wholesale_product', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('product_id')->unsigned();
            $table->integer('wholesale_id')->unsigned();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('wholesale_id')->references('id')->on('wholesale')->onDelete('cascade');

            $table->index('product_id');
            $table->index('wholesale_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wholesale_product');
    }
};
