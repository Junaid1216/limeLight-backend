<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageToUsersTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('area_sale_managers', function (Blueprint $table) {
            $table->string('image')->nullable()->after('password');
        });

        Schema::table('branch_managers', function (Blueprint $table) {
            $table->string('image')->nullable()->after('password');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('area_sale_managers', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('branch_managers', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
}
