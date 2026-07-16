<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddPeerBranchConversionSideMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $exists = DB::table('side_menus')->where('name', 'Peer Branch Conversion')->exists();

        if (!$exists) {
            DB::table('side_menus')->insert([
                'name' => 'Peer Branch Conversion',
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
        DB::table('side_menus')->where('name', 'Peer Branch Conversion')->delete();
    }
}
