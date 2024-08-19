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
        Schema::create('subscribers_list', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique(); // Added unique constraint for email
            $table->boolean('is_subscribed')->default(0);
            $table->string('token')->nullable();
            $table->unsignedInteger('customer_id')->nullable(); // Changed to unsignedInteger
            $table->unsignedInteger('channel_id'); // Changed to unsignedInteger
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');

            // Optional index for `email` column
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscribers_list');
    }
};

