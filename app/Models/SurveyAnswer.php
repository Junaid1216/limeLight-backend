<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyAnswer extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function submission()
    {
        return $this->belongsTo(SurveySubmission::class, 'survey_submission_id');
    }

    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }

    public function option()
    {
        return $this->belongsTo(SurveyOption::class, 'survey_option_id');
    }
}
