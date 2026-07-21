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
    
    $training = $request->id
            ? TrainingVideo::findOrFail($request->id)
            : new TrainingVideo();
    
      $image = $training->image;

     if ($request->hasFile('image')) {

            $file = $request->file('image');

            $filename = time().'.'.$file->getClientOriginalExtension();

            $file->move(public_path('admin/assets/images/users/'), $filename);

            $image = 'public/admin/assets/images/users/'.$filename;

        } else {

            $image = 'public/admin/assets/images/avator.png';

        }

        $audio = $training->audio;

        if ($request->hasFile('audio')) {
    
                $file = $request->file('audio');
    
                $filename = time().'.'.$file->getClientOriginalExtension();
    
                $file->move(public_path('admin/assets/training_videos/'), $filename);
    
                $audio = 'public/admin/assets/training_videos/'.$filename;
    
            } else {
    
                $audio = null;
    
            }

     if ($request->training_type === 'product') {

            $training->training_type = $request->training_type;

            $training->roles = $request->roles;

            $training->product_code = $request->product_code;

            $training->product_name = $request->product_name;

            $training->product_category = $request->product_category;

            $training->product_sub_category = $request->product_sub_category;

            $training->product_size = $request->product_size;

            $training->product_color = $request->product_color;

            $training->price = $request->price;

            $training->training_details = $request->training_details;

            $training->image = $image;

            $training->audio = $audio;

            $training->video_url = null;

        }


        /*
        |--------------------------------------------------------------------------
        | Display Training
        |--------------------------------------------------------------------------
        */

        elseif ($request->training_type === 'display') {
            $training->category = $request->category;

            $training->roles = $request->roles;

            $training->training_type = $request->training_type;

            $training->title = $request->title;

            $training->description = $request->description;

            $training->video_url = null;

            $training->image = $image;

            $training->audio = $audio;

            // Clear product data
            $training->product_code = null;
            $training->product_name = null;
            $training->product_category = null;
            $training->product_sub_category = null;
            $training->product_size = null;
            $training->product_color = null;
            $training->price = null;

        }


        /*
        |--------------------------------------------------------------------------
        | Customer Training
        |--------------------------------------------------------------------------
        */

        elseif ($request->training_type === 'customer') {
            $training->roles = $request->roles;

            $training->training_type = $request->training_type;

            $training->title = $request->title;

            $training->description = $request->description;

            $training->video_url = $request->video_url;

            $training->image = null;

            $training->audio = null;

            // Clear product data
            $training->product_code = null;
            $training->product_name = null;
            $training->product_category = null;
            $training->product_sub_category = null;
            $training->product_size = null;
            $training->product_color = null;
            $training->price = null;

        }


        $training->save();

    return back()->with('success','Training Video Saved Successfully');
}

public function delete($id)
{
    $video = TrainingVideo::findOrFail($id);
    $video->delete();

    return redirect()->route('training.video.index')->with('success', 'Training Video Deleted Successfully');
}
}
