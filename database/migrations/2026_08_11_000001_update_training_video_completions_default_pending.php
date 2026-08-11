<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateTrainingVideoCompletionsDefaultPending extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('training_video_completions')) {
            return;
        }

        // Normalize stored values and default to pending for new rows
        DB::table('training_video_completions')
            ->whereIn('status', ['complete', 'completed'])
            ->update(['status' => 'completed']);

        DB::statement("ALTER TABLE training_video_completions MODIFY status VARCHAR(255) NOT NULL DEFAULT 'pending'");
    }

    public function down()
    {
        if (!Schema::hasTable('training_video_completions')) {
            return;
        }

        DB::statement("ALTER TABLE training_video_completions MODIFY status VARCHAR(255) NOT NULL DEFAULT 'complete'");
    }
}
