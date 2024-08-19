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
        Schema::create('channel_translations', function (Blueprint $table) {
            $table->id(); // Uses unsignedBigInteger for the primary key
            $table->unsignedBigInteger('channel_id'); // Changed to unsignedBigInteger
            $table->string('locale')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('home_page_content')->nullable();
            $table->text('footer_content')->nullable();
            $table->text('maintenance_mode_text')->nullable();
            $table->json('home_seo')->nullable();
            $table->timestamps();

            // Unique constraint for channel and locale
            $table->unique(['channel_id', 'locale']);

            // Foreign key constraint
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channel_translations');
    }
};
