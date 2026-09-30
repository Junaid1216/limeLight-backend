<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserNotificationsAndEnhanceAdminNotifications extends Migration
{
    public function up()
    {
        if (Schema::hasTable('admin_notifications')) {
            Schema::table('admin_notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('admin_notifications', 'roles')) {
                    $table->json('roles')->nullable()->after('user_type');
                }
                if (!Schema::hasColumn('admin_notifications', 'is_deleted')) {
                    $table->boolean('is_deleted')->default(0)->after('description');
                }
            });
        }

        if (!Schema::hasTable('user_notifications')) {
            Schema::create('user_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_notification_id');
                $table->unsignedBigInteger('user_id');
                $table->string('user_type'); // staff | manager | asm
                $table->boolean('is_read')->default(0);
                $table->timestamps();

                $table->foreign('admin_notification_id')
                    ->references('id')
                    ->on('admin_notifications')
                    ->onDelete('cascade');

                $table->index(['user_id', 'user_type']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('user_notifications');

        if (Schema::hasTable('admin_notifications')) {
            Schema::table('admin_notifications', function (Blueprint $table) {
                if (Schema::hasColumn('admin_notifications', 'roles')) {
                    $table->dropColumn('roles');
                }
                if (Schema::hasColumn('admin_notifications', 'is_deleted')) {
                    $table->dropColumn('is_deleted');
                }
            });
        }
    }
}
