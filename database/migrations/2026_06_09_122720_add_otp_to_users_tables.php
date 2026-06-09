<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOtpToUsersTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sale_staff', function (Blueprint $table) {
            $table->string('otp')->nullable()->after('password');
        });

        Schema::table('area_sale_managers', function (Blueprint $table) {
            $table->string('otp')->nullable()->after('password');
        });

        Schema::table('branch_managers', function (Blueprint $table) {
            $table->string('otp')->nullable()->after('password');
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
            $table->dropColumn('otp');
        });

        Schema::table('area_sale_managers', function (Blueprint $table) {
            $table->dropColumn('otp');
        });

        Schema::table('branch_managers', function (Blueprint $table) {
            $table->dropColumn('otp');
        });
    }
}
