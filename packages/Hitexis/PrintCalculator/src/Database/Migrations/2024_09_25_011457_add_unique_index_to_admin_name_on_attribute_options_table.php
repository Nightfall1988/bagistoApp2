<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('attribute_options', function (Blueprint $table) {
            $table->unique(['attribute_id', 'admin_name'], 'attribute_admin_unique');
        });
    }

    public function down()
    {
        Schema::table('attribute_options', function (Blueprint $table) {
            $table->dropUnique('attribute_admin_unique');
        });
    }
};
