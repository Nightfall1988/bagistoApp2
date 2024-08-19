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
        Schema::create('channels', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->string('timezone')->nullable();
            $table->string('theme')->nullable();
            $table->string('hostname')->nullable();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->json('home_seo')->nullable();
            $table->boolean('is_maintenance_on')->default(0);
            $table->text('allowed_ips')->nullable();
            $table->unsignedInteger('root_category_id')->nullable(); // Ensure this matches the type in `categories` table
            $table->unsignedInteger('default_locale_id'); // Ensure this matches the type in `locales` table
            $table->unsignedInteger('base_currency_id'); // Ensure this matches the type in `currencies` table
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('root_category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('default_locale_id')->references('id')->on('locales');
            $table->foreign('base_currency_id')->references('id')->on('currencies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channels');
    }
};

