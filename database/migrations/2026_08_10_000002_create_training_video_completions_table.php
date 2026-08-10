<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingVideoCompletionsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('training_video_completions')) {
            return;
        }

        Schema::create('training_video_completions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('training_video_id');
            $table->string('user_type'); // sale_staff | branch_manager | area_sale_manager
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('complete'); // complete
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('training_video_id');
            $table->unique(
                ['training_video_id', 'user_type', 'user_id'],
                'training_user_unique'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('training_video_completions');
    }
}
