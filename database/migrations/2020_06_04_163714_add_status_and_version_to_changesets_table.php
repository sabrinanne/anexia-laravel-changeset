<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusAndVersionToChangesetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('changesets', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedInteger('version')->default(1);
        });


        DB::statement(
            "ALTER TABLE changesets MODIFY COLUMN changeset_type ENUM('I','U','D','AT','DT')"
        );

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('changesets', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('version');
        });

        DB::statement(
            "ALTER TABLE changesets MODIFY COLUMN changeset_type ENUM('I','U','D')"
        );

    }
}
