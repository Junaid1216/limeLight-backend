<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsReadToFeedBacksTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('feed_backs') || Schema::hasColumn('feed_backs', 'is_read')) {
            return;
        }

        Schema::table('feed_backs', function (Blueprint $table) {
            $table->boolean('is_read')->default(0)->after('feedback');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('feed_backs') || !Schema::hasColumn('feed_backs', 'is_read')) {
            return;
        }

        Schema::table('feed_backs', function (Blueprint $table) {
            $table->dropColumn('is_read');
        });
    }
}
