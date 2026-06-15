<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function getSurveyQuestions($role)
{
    $surveys = Survey::whereJsonContains('roles', $role)
        ->select('id', 'question')
        ->get();

    return response()->json([
        'status' => true,
        'message' => 'Survey questions retrieved successfully',
        'data' => $surveys
    ]);
}
}
