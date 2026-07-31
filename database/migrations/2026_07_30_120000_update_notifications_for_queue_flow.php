<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateNotificationsForQueueFlow extends Migration
{
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'sent_by')) {
                $table->string('sent_by')->default('admin')->after('is_sent');
            }
            if (!Schema::hasColumn('notifications', 'delete_by_admin')) {
                $table->boolean('delete_by_admin')->default(0)->after('sent_by');
            }
        });

        Schema::table('notification_targets', function (Blueprint $table) {
            if (!Schema::hasColumn('notification_targets', 'seen')) {
                $table->boolean('seen')->default(false)->after('targetable_type');
            }
        });

        foreach (['sale_staffs', 'branch_managers', 'area_sale_managers'] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'fcm_token')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->text('fcm_token')->nullable();
                });
            }
        }
    }

    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'sent_by')) {
                $table->dropColumn('sent_by');
            }
            if (Schema::hasColumn('notifications', 'delete_by_admin')) {
                $table->dropColumn('delete_by_admin');
            }
        });

        Schema::table('notification_targets', function (Blueprint $table) {
            if (Schema::hasColumn('notification_targets', 'seen')) {
                $table->dropColumn('seen');
            }
        });

        foreach (['sale_staffs', 'branch_managers', 'area_sale_managers'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'fcm_token')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('fcm_token');
                });
            }
        }
    }
}
