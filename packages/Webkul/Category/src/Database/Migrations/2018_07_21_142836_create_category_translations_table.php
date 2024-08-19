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
        Schema::create('category_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('category_id'); // Ensure it matches the referenced column
            $table->text('name');
            $table->string('slug');
            $table->string('url_path', 2048);
            $table->text('description')->nullable();
            $table->text('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->unsignedInteger('locale_id')->nullable(); // Ensure it matches the referenced column
            $table->string('locale');

            // Unique constraint to ensure no duplicate entries
            $table->unique(['category_id', 'slug', 'locale']);

            // Foreign key constraints with explicit names to avoid conflicts
            $table->foreign('category_id', 'fk_category_translations_category_id')
                  ->references('id')->on('categories')
                  ->onDelete('cascade');
                  
            $table->foreign('locale_id', 'fk_category_translations_locale_id')
                  ->references('id')->on('locales')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('category_translations');
    }
};
