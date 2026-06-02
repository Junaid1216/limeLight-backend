<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDesignationIdToTargetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('targets', function (Blueprint $table) {
            $table->string('designation_id')->nullable()->after('branch_manager_id');
            $table->string('branch_id')->nullable()->after('designation_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('targets', function (Blueprint $table) {
            $table->dropColumn(['designation_id', 'branch_id']);
        });
    }
}
