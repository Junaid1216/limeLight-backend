<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageToTrainingVideosTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('training_videos', function (Blueprint $table) {
            $table->string('audio')->nullable()->after('video_url');
            $table->string('image')->nullable()->after('audio');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('training_videos', function (Blueprint $table) {
            $table->dropColumn('audio');
            $table->dropColumn('image');
        });
    }
}
