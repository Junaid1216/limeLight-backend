<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourceToAdminNotificationsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('admin_notifications') || Schema::hasColumn('admin_notifications', 'source')) {
            return;
        }

        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->string('source')->nullable()->after('is_deleted');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('admin_notifications') || !Schema::hasColumn('admin_notifications', 'source')) {
            return;
        }

        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
}
