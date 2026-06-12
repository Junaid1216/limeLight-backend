<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBranchIdToSaleStaffTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sale_staff', function (Blueprint $table) {
            $table->string('image')->nullable()->after('otp');
            $table->unsignedBigInteger('branch_id')->nullable()->after('otp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sale_staff', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });
    }
}
