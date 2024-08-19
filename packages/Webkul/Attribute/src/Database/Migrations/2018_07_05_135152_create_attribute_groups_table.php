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
        Schema::create('attribute_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('attribute_family_id');
            $table->string('name');
            $table->integer('position');
            $table->boolean('is_user_defined')->default(1);

            $table->unique(['attribute_family_id', 'name']);

            // Explicitly name the foreign key constraint
            $table->foreign('attribute_family_id', 'fk_attribute_groups_family_id')
                  ->references('id')->on('attribute_families')
                  ->onDelete('cascade');
        });

        Schema::create('attribute_group_mappings', function (Blueprint $table) {
            $table->unsignedInteger('attribute_id');
            $table->unsignedInteger('attribute_group_id');
            $table->integer('position')->nullable();

            $table->primary(['attribute_id', 'attribute_group_id']);

            // Explicitly name the foreign key constraints
            $table->foreign('attribute_id', 'fk_group_mappings_attribute_id')
                  ->references('id')->on('attributes')
                  ->onDelete('cascade');

            $table->foreign('attribute_group_id', 'fk_group_mappings_group_id')
                  ->references('id')->on('attribute_groups')
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
        // Drop foreign keys explicitly
        Schema::table('attribute_group_mappings', function (Blueprint $table) {
            $table->dropForeign('fk_group_mappings_attribute_id');
            $table->dropForeign('fk_group_mappings_group_id');
        });

        Schema::dropIfExists('attribute_group_mappings');

        Schema::table('attribute_groups', function (Blueprint $table) {
            $table->dropForeign('fk_attribute_groups_family_id');
        });

        Schema::dropIfExists('attribute_groups');
    }
};
