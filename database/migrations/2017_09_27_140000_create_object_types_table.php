<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateObjectTypesTable extends Migration {

    public function up()
    {
        Schema::create('object_types', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255)->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('object_types');
    }
}