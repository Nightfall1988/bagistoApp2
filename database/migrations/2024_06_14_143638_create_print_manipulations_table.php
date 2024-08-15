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
        Schema::create('print_manipulations', function (Blueprint $table) {
            $table->id();
            $table->string('currency');
            $table->string('pricelist_valid_from');
            $table->string('pricelist_valid_until');
            $table->string('code');
            $table->string('description');
            $table->string('price');            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_manipulations');
    }
};
