<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeUsersRolePermissionsUserIdNullable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('users_role_permissions') || !Schema::hasColumn('users_role_permissions', 'user_id')) {
            return;
        }

        Schema::table('users_role_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down()
    {
        if (!Schema::hasTable('users_role_permissions') || !Schema::hasColumn('users_role_permissions', 'user_id')) {
            return;
        }

        Schema::table('users_role_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
}
