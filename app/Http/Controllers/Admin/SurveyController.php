<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function index()
    {
        $surveys = Survey::all();
        return view('admin.survey.index', compact('surveys'));
    }

    public function store(Request $request)
{
    $request->validate([
        'roles' => 'required',
        'question' => 'required',
    ]);

     
    
    Survey::updateOrCreate(
        ['id' => $request->id],
        [
            'roles' => $request->roles,
            'question' => $request->question,
        ]
    );

    return back()->with('success','Survey Saved Successfully');
}

public function delete($id)
{
    $survey = Survey::findOrFail($id);
    $survey->delete();

    return redirect()->route('survey.index')->with('success', 'Survey Deleted Successfully');
}
}
