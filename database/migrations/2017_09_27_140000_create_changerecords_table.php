<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateChangerecordsTable extends Migration {

    public function up()
    {
        Schema::create('changerecords', function(Blueprint $table) {
            $table->increments('id');
            $table->string('field_name', 255);
            $table->unsignedInteger('changeset_id');
            $table->text('display');
            $table->boolean('is_deletion')->default(0);
            $table->boolean('is_related')->default(0);
            $table->text('new_value')->nullable();
            $table->text('old_value')->nullable();
            $table->text('related_display');
            $table->unsignedInteger('related_object_type_id');
            $table->string('related_object_uuid', 255);
        });
    }

    public function down()
    {
        Schema::drop('changerecords');
    }
}