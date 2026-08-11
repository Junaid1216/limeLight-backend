<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\BranchManager;
use App\Models\SaleStaff;
use App\Models\TrainingVideo;
use App\Models\TrainingVideoCompletion;
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

        $this->seedPendingStatusForRoles($training);

        return back()->with('success','Training Module Saved Successfully');
}

/**
 * When a training is assigned to roles, default user status = pending.
 */
private function seedPendingStatusForRoles(TrainingVideo $training): void
{
    $roles = $training->roles ?? [];
    if (!is_array($roles) || empty($roles)) {
        return;
    }

    $users = [];

    if (in_array('sales_staff', $roles, true)) {
        foreach (SaleStaff::query()->select('id')->cursor() as $staff) {
            $users[] = ['user_type' => 'sale_staff', 'user_id' => $staff->id];
        }
    }

    if (in_array('branch_manager', $roles, true)) {
        foreach (BranchManager::query()->select('id')->cursor() as $manager) {
            $users[] = ['user_type' => 'branch_manager', 'user_id' => $manager->id];
        }
    }

    if (in_array('asm', $roles, true)) {
        foreach (AreaSaleManager::query()->select('id')->cursor() as $asm) {
            $users[] = ['user_type' => 'area_sale_manager', 'user_id' => $asm->id];
        }
    }

    if (empty($users)) {
        return;
    }

    $existing = TrainingVideoCompletion::where('training_video_id', $training->id)
        ->get(['user_type', 'user_id'])
        ->map(function ($row) {
            return $row->user_type . ':' . $row->user_id;
        })
        ->all();

    $now = now();
    $rows = [];

    foreach ($users as $user) {
        $key = $user['user_type'] . ':' . $user['user_id'];
        if (in_array($key, $existing, true)) {
            continue;
        }

        $rows[] = [
            'training_video_id' => $training->id,
            'user_type' => $user['user_type'],
            'user_id' => $user['user_id'],
            'status' => 'pending',
            'completed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    foreach (array_chunk($rows, 500) as $chunk) {
        TrainingVideoCompletion::insert($chunk);
    }
}

public function delete($id)
{
    $video = TrainingVideo::findOrFail($id);
    $video->delete();

    return redirect()->route('training.video.index')->with('success', 'Training Module Deleted Successfully');
}
}
