<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedBackController extends Controller
{
    public function stafffeedback(Request $request)
{
    $user = Auth::user();
    // $request->validate([
    //     'code'      => 'required',
    //     'name'      => 'required',
    //     'branch_id' => 'required|exists:branches,id',
    //     'feedback'  => 'required',
    // ]);

    $feedback = Feedback::create([
        'code'      => $request->code,
        'name'      => $request->name,
        'branch_name' => $request->branch_name,
        'feedback'  => $request->feedback,
    ]);

    return response()->json([
        'status'  => true,
        'message' => 'Feedback submitted successfully',
        'data'    => $feedback
    ]);
}

public function asmfeedback(Request $request)
{
    $user = Auth::user();
    // $request->validate([
    //     'code'      => 'required',
    //     'name'      => 'required',
    //     'region_name' => 'required|exists:branches,id',
    //     'feedback'  => 'required',
    // ]);

    $feedback = Feedback::create([
        'code'      => $request->code,
        'name'      => $request->name,
        'region_name' => $request->region_name,
        'feedback'  => $request->feedback,
    ]);

    return response()->json([
        'status'  => true,
        'message' => 'Feedback submitted successfully',
        'data'    => $feedback
    ]);
}
}
