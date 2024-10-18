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
        Schema::table('print_manipulations', function (Blueprint $table) {
            // Add a unique index to the 'code' column
            $table->unique('code');
        });
    }

    public function down()
    {
        Schema::table('print_manipulations', function (Blueprint $table) {
            // Drop the unique index from the 'code' column
            $table->dropUnique(['code']);
        });
    }
};
