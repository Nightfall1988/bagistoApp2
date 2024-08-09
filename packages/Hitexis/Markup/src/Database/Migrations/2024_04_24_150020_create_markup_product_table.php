<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('markup_product', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('product_id')->unsigned();
            $table->integer('markup_id')->unsigned();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('markup_id')->references('id')->on('markup')->onDelete('cascade');

            $table->index('product_id');
            $table->index('markup_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('markup_product');
    }
};
