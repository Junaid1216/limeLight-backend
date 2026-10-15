<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedAdminSectionPermissions extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('side_menus') || !Schema::hasTable('permissions') || !Schema::hasTable('side_menu_has_permissions')) {
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

        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['view', 'create', 'edit', 'delete'])
            ->pluck('id', 'name');

        $sections = [
            'Roles' => ['view', 'create', 'edit', 'delete'],
            'Sub Admins' => ['view', 'create', 'edit', 'delete'],
            'Cities' => ['view', 'create', 'edit', 'delete'],
            'Designations' => ['view', 'create', 'edit', 'delete'],
            'Regions' => ['view', 'create', 'edit', 'delete'],
            'Area Sale Managers' => ['view', 'create', 'edit', 'delete'],
            'Branches' => ['view', 'create', 'edit', 'delete'],
            'Branch Managers' => ['view', 'create', 'edit', 'delete'],
            'Sale Staff' => ['view', 'create', 'edit', 'delete'],
            'Training Modules' => ['view', 'create', 'edit', 'delete'],
            'Display Categories' => ['view', 'create', 'edit', 'delete'],
            'Surveys' => ['view', 'create', 'edit', 'delete'],
            'Announcements' => ['view', 'create', 'edit', 'delete'],
            'Targets' => ['view', 'create', 'edit'],
            'Commissions' => ['view', 'create', 'delete'],
            'Hierarchy' => ['view', 'edit'],
            'Line Items' => ['view', 'edit'],
            'Slip Bound Incentives' => ['view', 'edit'],
            'Assigned Targets' => ['view', 'edit'],
            'Privacy & Policy' => ['view', 'edit'],
            'Terms & Conditions' => ['view', 'edit'],
            'Feedback' => ['view', 'delete'],
            'Reportings' => ['view'],
            'Sales History' => ['view'],
            'Peer Branch Conversion' => ['view'],
            'Users' => ['view', 'create', 'edit', 'delete'],
            'Blogs' => ['view', 'create', 'edit', 'delete'],
            'Faqs' => ['view', 'create', 'edit', 'delete'],
            'Notifications' => ['view', 'create', 'delete'],
            'Contact us' => ['view', 'create', 'edit'],
            'About us' => ['view', 'edit'],
        ];

        foreach ($sections as $menuName => $actions) {
            $menu = DB::table('side_menus')->where('name', $menuName)->first();
            if (!$menu) {
                $menuId = DB::table('side_menus')->insertGetId([
                    'name' => $menuName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $menuId = $menu->id;
            }

            foreach ($actions as $action) {
                $permissionId = $permissionIds[$action] ?? null;
                if (!$permissionId) {
                    continue;
                }

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
    }

    public function down()
    {
        // Keep seeded menus; roles may already be assigned against them.
    }
}
