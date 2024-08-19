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
        Schema::create('wishlist', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('channel_id');  // Use unsignedInteger for consistency
            $table->unsignedInteger('product_id');  // Use unsignedInteger for consistency
            $table->unsignedInteger('customer_id'); // Use unsignedInteger for consistency
            $table->json('item_options')->nullable();
            $table->date('moved_to_cart')->nullable();
            $table->boolean('shared')->default(false); // Use default value for booleans
            $table->date('time_of_moving')->nullable();
            $table->json('additional')->nullable();
            $table->timestamps();

            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wishlist');
    }
};
