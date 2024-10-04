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
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('print_price')->nullable();
            $table->string('print_single_price')->nullable();
            $table->string('print_manipulation_cost')->nullable();
            $table->string('print_setup')->nullable();
            $table->string('print_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('print_price');
            $table->dropColumn('print_single_price');
            $table->dropColumn('print_manipulation_cost');
            $table->dropColumn('print_setup');
            $table->dropColumn('print_name');
        });
    }
};
