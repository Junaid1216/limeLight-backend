<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyOption;
use App\Models\SurveyQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurveyController extends Controller
{
    public function index()
    {
        $surveys = Survey::with(['questions' => function ($q) {
            $q->orderBy('sort_order');
        }])->orderByDesc('id')->get();

        return view('admin.survey.index', compact('surveys'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'roles' => 'required',
            'title' => 'required|string|max:255',
            'question' => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        DB::transaction(function () use ($request) {
            $survey = Survey::updateOrCreate(
                ['id' => $request->id],
                [
                    'title' => $request->title,
                    'status' => $request->status ?: 'active',
                    'roles' => $request->roles,
                    'question' => $request->question,
                ]
            );

            // On create: add the question with default High / Fair / Low options (Figma).
            // On edit: if no questions yet, create one; otherwise update first question text.
            $question = $survey->questions()->orderBy('sort_order')->first();

            if (!$question) {
                $question = SurveyQuestion::create([
                    'survey_id' => $survey->id,
                    'question' => $request->question,
                    'is_required' => true,
                    'sort_order' => 1,
                ]);

                foreach (['High', 'Fair', 'Low'] as $index => $label) {
                    SurveyOption::create([
                        'survey_question_id' => $question->id,
                        'label' => $label,
                        'sort_order' => $index + 1,
                    ]);
                }
            } else {
                $question->update(['question' => $request->question]);

                if ($question->options()->count() === 0) {
                    foreach (['High', 'Fair', 'Low'] as $index => $label) {
                        SurveyOption::create([
                            'survey_question_id' => $question->id,
                            'label' => $label,
                            'sort_order' => $index + 1,
                        ]);
                    }
                }
            }
        });

        return back()->with('success', 'Survey Saved Successfully');
    }

    /**
     * Add another question (High/Fair/Low) to an existing survey.
     */
    public function addQuestion(Request $request, $id)
    {
        $request->validate([
            'question' => 'required|string|max:255',
        ]);

        $survey = Survey::findOrFail($id);
        $sort = (int) $survey->questions()->max('sort_order') + 1;

        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question' => $request->question,
            'is_required' => true,
            'sort_order' => $sort,
        ]);

        foreach (['High', 'Fair', 'Low'] as $index => $label) {
            SurveyOption::create([
                'survey_question_id' => $question->id,
                'label' => $label,
                'sort_order' => $index + 1,
            ]);
        }

        return back()->with('success', 'Question added successfully');
    }

    public function delete($id)
    {
        $survey = Survey::findOrFail($id);
        $survey->delete();

        return redirect()->route('survey.index')->with('success', 'Survey Deleted Successfully');
    }
}
