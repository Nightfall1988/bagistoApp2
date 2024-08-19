<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if columns exist before dropping them
        if (Schema::hasColumn('categories', 'image') && Schema::hasColumn('categories', 'category_banner')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn(['image', 'category_banner']);
            });
        }

        // Add new columns
        Schema::table('categories', function (Blueprint $table) {
            $table->text('logo_path')->nullable()->after('position');
            $table->text('banner_path')->nullable()->after('additional');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if columns exist before dropping them
        if (Schema::hasColumn('categories', 'logo_path') && Schema::hasColumn('categories', 'banner_path')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn(['logo_path', 'banner_path']);
            });
        }

        // Re-add the previous columns
        Schema::table('categories', function (Blueprint $table) {
            $table->text('image')->nullable()->after('position');
            $table->text('category_banner')->nullable()->after('additional');
        });
    }
};
