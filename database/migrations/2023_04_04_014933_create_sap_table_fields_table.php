<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSapTableFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sap_table_fields', function (Blueprint $table) {
            $table->id();
            $table->integer('sap_table_id');
            $table->string('field_name');
            $table->string('description');
            $table->string('type');
            $table->string('length')->nullable();
            $table->string('default_value')->nullable();
            $table->boolean('has_options');
            $table->boolean('is_multiple');
            $table->boolean('is_required');
            $table->boolean('is_migrated');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sap_table_fields');
    }
}
