<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateDisplayCategoriesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('display_categories')) {
            Schema::create('display_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('status')->default(1);
                $table->timestamps();
            });
        }

        $now = now();
        $defaults = ['Unstitched', 'RTW', 'Co-ords', 'Western'];

        foreach ($defaults as $name) {
            $slug = Str::slug($name);
            $existsCat = DB::table('display_categories')->where('slug', $slug)->exists();
            if (!$existsCat) {
                DB::table('display_categories')->insert([
                    'name' => $name,
                    'slug' => $slug,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $exists = Schema::hasTable('side_menus')
            && DB::table('side_menus')->where('name', 'Display Categories')->exists();

        if (Schema::hasTable('side_menus') && !$exists) {
            DB::table('side_menus')->insert([
                'name' => 'Display Categories',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('side_menus') && Schema::hasTable('side_menu_has_permissions') && Schema::hasTable('permissions')) {
            $menu = DB::table('side_menus')->where('name', 'Display Categories')->first();
            if ($menu) {
                $permissionIds = DB::table('permissions')->pluck('id');
                foreach ($permissionIds as $permissionId) {
                    $linkExists = DB::table('side_menu_has_permissions')
                        ->where('side_menu_id', $menu->id)
                        ->where('permission_id', $permissionId)
                        ->exists();

                    if (!$linkExists) {
                        DB::table('side_menu_has_permissions')->insert([
                            'side_menu_id' => $menu->id,
                            'permission_id' => $permissionId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('side_menus')) {
            $menu = DB::table('side_menus')->where('name', 'Display Categories')->first();
            if ($menu) {
                if (Schema::hasTable('side_menu_has_permissions')) {
                    DB::table('side_menu_has_permissions')->where('side_menu_id', $menu->id)->delete();
                }
                DB::table('side_menus')->where('id', $menu->id)->delete();
            }
        }

        Schema::dropIfExists('display_categories');
    }
}
