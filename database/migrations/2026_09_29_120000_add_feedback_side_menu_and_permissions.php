<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddFeedbackSideMenuAndPermissions extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('side_menus') || !Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        foreach (['view', 'create', 'edit', 'delete'] as $permName) {
            $exists = DB::table('permissions')->where('name', $permName)->exists();
            if (!$exists) {
                DB::table('permissions')->insert([
                    'name' => $permName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $menu = DB::table('side_menus')->where('name', 'Feedback')->first();
        if (!$menu) {
            $menuId = DB::table('side_menus')->insertGetId([
                'name' => 'Feedback',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $menuId = $menu->id;
        }

        if (!Schema::hasTable('side_menu_has_permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['view', 'delete'])
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            $linked = DB::table('side_menu_has_permissions')
                ->where('side_menu_id', $menuId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (!$linked) {
                DB::table('side_menu_has_permissions')->insert([
                    'side_menu_id' => $menuId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        if (!Schema::hasTable('side_menus')) {
            return;
        }

        $menu = DB::table('side_menus')->where('name', 'Feedback')->first();
        if (!$menu) {
            return;
        }

        if (Schema::hasTable('side_menu_has_permissions')) {
            DB::table('side_menu_has_permissions')->where('side_menu_id', $menu->id)->delete();
        }

        DB::table('side_menus')->where('id', $menu->id)->delete();
    }
}
