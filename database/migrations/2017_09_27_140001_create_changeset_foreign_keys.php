<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateChangeSetForeignKeys extends Migration {

    public function up()
    {
        Schema::table('changesets', function(Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
        Schema::table('changesets', function(Blueprint $table) {
            $table->foreign('object_type_id')->references('id')->on('object_types')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
        Schema::table('changerecords', function(Blueprint $table) {
            $table->foreign('changeset_id')->references('id')->on('changesets')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::table('changesets', function(Blueprint $table) {
            $table->dropForeign('changesets_user_id_foreign');
        });
        Schema::table('changesets', function(Blueprint $table) {
            $table->dropForeign('changesets_object_type_id_foreign');
        });
        Schema::table('changerecords', function(Blueprint $table) {
            $table->dropForeign('changerecords_changeset_id_foreign');
        });
    }
}