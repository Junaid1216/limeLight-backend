<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\BranchManager;
use App\Models\SaleStaff;
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
            '200',
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

     public function trainingProduct(Request $request)
    {
        try {

            $user = Auth::user();
    
            // Get user's role
             $role = null;

        // Check ASM
            if (AreaSaleManager::where('employee_id', $user->employee_id)->exists()) {
                $role = 'asm';
            }

            // Check Branch Manager
            elseif (BranchManager::where('employee_id', $user->employee_id)->exists()) {
                $role = 'branch_manager';
            }

            // Check Sale Staff
            elseif (SaleStaff::where('employee_id', $user->employee_id)->exists()) {
                $role = 'sales_staff';
            }

            if (!$role) {
                return response()->json([
                    'status' => 404,
                    'message' => 'User role could not be determined',
                    'data' => []
                ]);
            }
            // Get product training only
            $trainings = TrainingVideo::where('training_type', 'product')
                ->whereJsonContains('roles', $role)
                ->latest()
                ->get();
            

            $data = $trainings->map(function ($training) {

                return [
                    'id' => $training->id,

                    'training_type' => $training->training_type,

                    'product_name' => $training->product_name,

                    'product_code' => $training->product_code,

                    'product_category' => $training->product_category,

                    'product_sub_category' => $training->product_sub_category,

                    'color' => $training->product_color,

                    'price' => $training->price,

                    'training_details' => $training->training_details
                    ? preg_replace('/\s+/', ' ', trim($training->training_details))
                    : null,

                    'image' =>  asset($training->image),
                        

                    'audio' => asset($training->audio),
                ];
            });

            return response()->json([
                'status' => 200,
                'message' => 'Product training retrieved successfully',
                'data' => $data
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function trainingDisplay(Request $request)
{
    try {

        $user = Auth::user();

         // Get user's role
             $role = null;

        // Check ASM
            if (AreaSaleManager::where('employee_id', $user->employee_id)->exists()) {
                $role = 'asm';
            }

            // Check Branch Manager
            elseif (BranchManager::where('employee_id', $user->employee_id)->exists()) {
                $role = 'branch_manager';
            }

            // Check Sale Staff
            elseif (SaleStaff::where('employee_id', $user->employee_id)->exists()) {
                $role = 'sales_staff';
            }

            if (!$role) {
                return response()->json([
                    'status' => 404,
                    'message' => 'User role could not be determined',
                    'data' => []
                ]);
            }

        $trainings = TrainingVideo::where('training_type', 'display')
            ->whereJsonContains('roles', $role)

            // Optional category filter
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->where('category', $request->category);
            })

            ->latest()
            ->get();

        $data = $trainings->map(function ($training) {

            return [
                'id' => $training->id,

                'training_type' => $training->training_type,

                'title' => $training->title,

                'description' => $training->description,

                'image' => $training->image
                    ? asset($training->image)
                    : null,

                'audio' => $training->audio
                    ? asset($training->audio)
                    : null,
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'Display training retrieved successfully',
            'data' => $data
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'status' => 500,
            'message' => 'Something went wrong',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
