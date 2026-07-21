<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddAssignedTargetsSideMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $exists = DB::table('side_menus')->where('name', 'Assigned Targets')->exists();

        if (!$exists) {
            DB::table('side_menus')->insert([
                'name' => 'Assigned Targets',
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
        DB::table('side_menus')->where('name', 'Assigned Targets')->delete();
    }
}
