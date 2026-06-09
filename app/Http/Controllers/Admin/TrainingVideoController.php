<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingVideo;
use Illuminate\Http\Request;

class TrainingVideoController extends Controller
{
    public function index()
    {
        $videos = TrainingVideo::all();
        return view('admin.trainingvideo.index', compact('videos'));
    }

    public function store(Request $request)
{
    $request->validate([
        'roles' => 'required',
        'title' => 'required',
        'video_url' => 'required',
        'description' => 'required',
    ]);

    TrainingVideo::updateOrCreate(
        ['id' => $request->id],
        [
            'roles' => $request->roles,
            'title' => $request->title,
            'video_url' => $request->video_url,
            'description' => $request->description,
        ]
    );

    return back()->with('success','Training Video Saved Successfully');
}

public function delete($id)
{
    $video = TrainingVideo::findOrFail($id);
    $video->delete();

    return redirect()->route('training.video.index')->with('success', 'Training Video Deleted Successfully');
}
}
