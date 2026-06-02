<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToggleToBlogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->boolean('toggle')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
   public function down()
{
    Schema::table('blogs', function (Blueprint $table) {
        $table->dropColumn('toggle');
    });
}

}
