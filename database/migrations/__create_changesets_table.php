<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateChangesetsTable extends Migration {

    public function up()
    {
        Schema::create('changesets', function(Blueprint $table) {
            $table->increments('id');
            $table->string('object_uuid', 255);
            $table->enum('changeset_type', array('I', 'U', 'D')); // I = INSERT, U = UPDATE, D = DELETE
            $table->date('date');
            $table->unsignedInteger('object_type_id');
            $table->unsignedInteger('user_id');
        });
    }

    public function down()
    {
        Schema::drop('changesets');
    }
}