<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateChangesetsTable extends Migration {

    public function up()
    {
        Schema::create('changesets', function(Blueprint $table) {
            $table->increments('id');
            $table->string('action_id', 255);
            $table->enum('changeset_type', array(
                \Anexia\Changeset\Changeset::CHANGESET_TYPE_INSERT,
                \Anexia\Changeset\Changeset::CHANGESET_TYPE_UPDATE,
                \Anexia\Changeset\Changeset::CHANGESET_TYPE_DELETE,
            )); // I = INSERT, U = UPDATE, D = DELETE
            $table->text('display');
            $table->unsignedInteger('object_type_id');
            $table->string('object_uuid', 255);
            $table->unsignedInteger('related_changeset_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('changesets');
    }
}