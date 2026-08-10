<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillSurveyQuestionsFromLegacy extends Migration
{
    public function up()
    {
        $surveys = DB::table('surveys')->get();

        foreach ($surveys as $survey) {
            $exists = DB::table('survey_questions')->where('survey_id', $survey->id)->exists();
            if ($exists) {
                continue;
            }

            $text = trim((string) ($survey->question ?? ''));
            if ($text === '') {
                continue;
            }

            $questionId = DB::table('survey_questions')->insertGetId([
                'survey_id' => $survey->id,
                'question' => $text,
                'is_required' => 1,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (['High', 'Fair', 'Low'] as $index => $label) {
                DB::table('survey_options')->insert([
                    'survey_question_id' => $questionId,
                    'label' => $label,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        // Intentionally left empty — backfilled data should not be auto-removed.
    }
}
