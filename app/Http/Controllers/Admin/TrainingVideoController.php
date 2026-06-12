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
        'description' => 'required',
    ]);

     if ($request->hasFile('image')) {

            $file = $request->file('image');

            $filename = time().'.'.$file->getClientOriginalExtension();

            $file->move(public_path('admin/assets/images/users/'), $filename);

            $image = 'public/admin/assets/images/users/'.$filename;

        } else {

            $image = 'public/admin/assets/images/avator.png';

        }

        if ($request->hasFile('audio')) {
    
                $file = $request->file('audio');
    
                $filename = time().'.'.$file->getClientOriginalExtension();
    
                $file->move(public_path('admin/assets/training_videos/'), $filename);
    
                $audio = 'public/admin/assets/training_videos/'.$filename;
    
            } else {
    
                $audio = null;
    
            }

    TrainingVideo::updateOrCreate(
        ['id' => $request->id],
        [
            'roles' => $request->roles,
            'title' => $request->title,
            'video_url' => $request->video_url,
            'description' => $request->description,
            'image' => $image,
            'audio' => $audio,
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
