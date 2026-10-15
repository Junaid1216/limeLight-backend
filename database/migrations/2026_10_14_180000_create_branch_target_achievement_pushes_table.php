<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBranchTargetAchievementPushesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('branch_target_achievement_pushes')) {
            return;
        }

        Schema::create('branch_target_achievement_pushes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('month');
            $table->string('year');
            $table->timestamps();

            $table->unique(['branch_id', 'month', 'year'], 'branch_target_achieved_push_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('branch_target_achievement_pushes');
    }
}
