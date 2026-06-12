<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\TrainingVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainingVideoController extends Controller
{
    public function getTrainingVideos(Request $request)
    {
         try {

        $role = $request->role;

        $videos = TrainingVideo::whereJsonContains('roles', $role)
                    ->select(
                        'id',
                        'title',
                        'description',
                        'video_url'
                    )
                    ->latest()
                    ->get();

        return ResponseHelper::success(
            $videos,
            'Training videos retrieved successfully',
            'true',
            200
        );

    } catch (\Exception $e) {

        return ResponseHelper::error(
            $e->getMessage(),
            'An error occurred while retrieving training videos',
            'error',
            500
        );
    }
    }
}
