<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;

class CreateChangeSetForeignKeys extends Migration {

	public function up()
	{
		Schema::table('users', function(Blueprint $table) {
			$table->foreign('changeset_id')->references('id')->on('changesets')
						->onDelete('restrict')
						->onUpdate('cascade');
		});
		Schema::table('changesets', function(Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('users')
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
		Schema::table('users', function(Blueprint $table) {
			$table->dropForeign('users_changeset_id_foreign');
		});
		Schema::table('changesets', function(Blueprint $table) {
			$table->dropForeign('changesets_user_id_foreign');
		});
		Schema::table('changerecords', function(Blueprint $table) {
			$table->dropForeign('changerecords_changeset_id_foreign');
		});
	}
}