<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddSalesHistorySideMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $exists = DB::table('side_menus')->where('name', 'Sales History')->exists();

        if (!$exists) {
            DB::table('side_menus')->insert([
                'name' => 'Sales History',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('side_menus')->where('name', 'Sales History')->delete();
    }
}
