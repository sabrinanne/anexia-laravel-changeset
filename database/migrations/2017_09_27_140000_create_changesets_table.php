<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateChangesetsTable extends Migration {

    public function up()
    {
        Schema::create('changesets', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('action_id');
            $table->enum('changeset_type', array(
                \Anexia\Changeset\Changeset::CHANGESET_TYPE_INSERT,
                \Anexia\Changeset\Changeset::CHANGESET_TYPE_UPDATE,
                \Anexia\Changeset\Changeset::CHANGESET_TYPE_DELETE,
            )); // I = INSERT, U = UPDATE, D = DELETE
            $table->date('date');
            $table->text('display');
            $table->boolean('is_related')->default(0);
            $table->unsignedInteger('object_type_id');
            $table->string('object_uuid', 255);
            $table->unsignedInteger('user_id')->nullable();
        });
    }

    public function down()
    {
        Schema::drop('changesets');
    }
}