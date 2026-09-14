<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WidenCommissionRoleColumnsToString extends Migration
{
    public function up()
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->string('role', 64)->change();
        });

        if (Schema::hasTable('commission_histories')) {
            Schema::table('commission_histories', function (Blueprint $table) {
                $table->string('role', 64)->change();
            });
        }
    }

    public function down()
    {
        // Reverting to a tight enum would fail if designation-specific roles exist.
        Schema::table('commissions', function (Blueprint $table) {
            $table->string('role', 64)->change();
        });

        if (Schema::hasTable('commission_histories')) {
            Schema::table('commission_histories', function (Blueprint $table) {
                $table->string('role', 64)->change();
            });
        }
    }
}
