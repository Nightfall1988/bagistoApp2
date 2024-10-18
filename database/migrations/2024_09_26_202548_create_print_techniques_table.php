<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('print_techniques', function (Blueprint $table) {
            $table->string('technique_id')->primary()->unique();
            $table->string('description');
            $table->string('pricing_type');
            $table->decimal('setup', 8, 2);
            $table->decimal('setup_repeat', 8, 2);
            $table->boolean('next_colour_cost_indicator');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('print_techniques');
    }
};
