<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('position_print_techniques', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('printing_position_id');
            $table->string('print_technique_id');
            $table->boolean('default')->default(false);
            $table->integer('max_colours');
            $table->timestamps();

            $table->foreign('printing_position_id')
                ->references('id')
                ->on('printing_positions')
                ->onDelete('cascade');

            $table->foreign('print_technique_id')
                ->references('technique_id')
                ->on('print_techniques')
                ->onDelete('cascade');

            $table->unique(['printing_position_id', 'print_technique_id'], 'attribute_position_id_print_id_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('position_print_techniques');
    }
};
