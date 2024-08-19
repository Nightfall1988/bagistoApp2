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
        // Ensure no existing table conflicts
        if (Schema::hasTable('customers')) {
            Schema::dropIfExists('customers');
        }

        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender', 50)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('phone')->unique()->nullable();
            $table->string('image')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->string('password')->nullable();
            $table->string('api_token', 80)->unique()->nullable()->default(null);
            $table->unsignedInteger('customer_group_id')->nullable(); // Use unsignedInteger
            $table->boolean('subscribed_to_news_letter')->default(false); // Use default boolean value
            $table->boolean('is_verified')->default(false); // Use default boolean value
            $table->tinyInteger('is_suspended')->unsigned()->default(0);
            $table->string('token')->nullable();
            $table->text('notes')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('customer_group_id')
                  ->references('id')
                  ->on('customer_groups')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
};
