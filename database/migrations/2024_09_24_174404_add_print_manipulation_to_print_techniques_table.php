<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('print_techniques', function (Blueprint $table) {
            $table->unsignedBigInteger('print_manipulation_id')->nullable()->after('id');

            $table->foreign('print_manipulation_id')
                  ->references('id')->on('print_manipulations')
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
        Schema::table('print_techniques', function (Blueprint $table) {
            $table->dropForeign(['print_manipulation_id']);
            $table->dropColumn('print_manipulation_id');
        });
    }
};


