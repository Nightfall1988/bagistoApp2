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
            $table->string('code')->unique(); // Added unique constraint to 'code'
            $table->string('timezone')->nullable();
            $table->string('theme')->nullable();
            $table->string('hostname')->nullable();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->json('home_seo')->nullable();
            $table->boolean('is_maintenance_on')->default(false); // Use boolean false
            $table->text('allowed_ips')->nullable();
            $table->unsignedInteger('root_category_id')->nullable(); // Ensured unsigned
            $table->unsignedInteger('default_locale_id'); // Ensured unsigned
            $table->unsignedInteger('base_currency_id'); // Ensured unsigned
            $table->timestamps();

            $table->foreign('root_category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('default_locale_id')->references('id')->on('locales');
            $table->foreign('base_currency_id')->references('id')->on('currencies');
        });

        Schema::create('channel_locales', function (Blueprint $table) {
            $table->unsignedInteger('channel_id'); // Ensured unsigned
            $table->unsignedInteger('locale_id'); // Ensured unsigned

            $table->primary(['channel_id', 'locale_id']);
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            $table->foreign('locale_id')->references('id')->on('locales')->onDelete('cascade');
        });

        Schema::create('channel_currencies', function (Blueprint $table) {
            $table->unsignedInteger('channel_id'); // Ensured unsigned
            $table->unsignedInteger('currency_id'); // Ensured unsigned

            $table->primary(['channel_id', 'currency_id']);
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('channel_currencies', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropForeign(['currency_id']);
        });

        Schema::table('channel_locales', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropForeign(['locale_id']);
        });

        Schema::dropIfExists('channel_currencies');
        Schema::dropIfExists('channel_locales');
        Schema::dropIfExists('channels');
    }
};
