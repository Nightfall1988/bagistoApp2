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
        Schema::create('markup', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('amount')->nullable();
            $table->string('percentage')->nullable();
            $table->string('markup_unit');
            $table->string('currency');
            $table->string('markup_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('markup');
    }
};
