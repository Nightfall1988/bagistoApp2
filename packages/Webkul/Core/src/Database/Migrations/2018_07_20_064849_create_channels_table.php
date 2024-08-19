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
        // Ensure the table does not already exist
        if (!Schema::hasTable('channels')) {
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
                $table->unsignedInteger('root_category_id')->nullable(); // Ensuring proper unsigned type
                $table->unsignedInteger('default_locale_id');
                $table->unsignedInteger('base_currency_id');
                $table->timestamps();

                // Define foreign keys with explicit names to avoid conflicts
                $table->foreign('root_category_id', 'fk_channels_root_category')
                      ->references('id')->on('categories')
                      ->onDelete('set null');

                $table->foreign('default_locale_id', 'fk_channels_default_locale')
                      ->references('id')->on('locales');

                $table->foreign('base_currency_id', 'fk_channels_base_currency')
                      ->references('id')->on('currencies');
            });
        }

        // Ensure no existing constraints conflict with this migration
        // This step might involve manual inspection if you're avoiding raw SQL
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Check if the table exists before trying to drop it
        if (Schema::hasTable('channels')) {
            Schema::dropIfExists('channels');
        }
    }
};
