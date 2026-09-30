<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUserTypeToFeedBacksTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('feed_backs')) {
            return;
        }

        Schema::table('feed_backs', function (Blueprint $table) {
            if (!Schema::hasColumn('feed_backs', 'user_type')) {
                $table->string('user_type')->nullable()->after('id');
            }
        });

        // Backfill existing rows
        DB::table('feed_backs')
            ->whereNotNull('region_name')
            ->where('region_name', '!=', '')
            ->whereNull('user_type')
            ->update(['user_type' => 'area_sale_manager']);

        DB::table('feed_backs')
            ->where(function ($q) {
                $q->whereNull('region_name')->orWhere('region_name', '');
            })
            ->whereNull('user_type')
            ->update(['user_type' => 'sale_staff']);
    }

    public function down()
    {
        if (!Schema::hasTable('feed_backs') || !Schema::hasColumn('feed_backs', 'user_type')) {
            return;
        }

        Schema::table('feed_backs', function (Blueprint $table) {
            $table->dropColumn('user_type');
        });
    }
}
