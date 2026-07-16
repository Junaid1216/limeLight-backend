<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssignedTarget;
use App\Models\Notification;
use App\Models\Target;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MonthlyTargetController extends Controller
{
    public function getMonthlyTarget()
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'User not authenticated'
        ], 401);
    }

    $targets = Target::where('designation_id', $user->designation_id)
        ->where('branch_id', $user->branch_id)
        ->select('category', 'monthly_target')
        ->get();

    return response()->json([
        'status' => 200,
        'message' => 'Monthly targets retrieved successfully',
        'data' => $targets
    ]);
}



    public function getAssignedTargets()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'User not authenticated',
            ], 401);
        }

        $targets = Target::where('branch_id', $user->branch_id)
            ->where('designation_id', $user->designation_id)
            ->get();

        $data = [
            'garments' => 0,
            'unstitched' => 0,
            'accessories' => 0,
        ];

         foreach ($targets as $target) {

        $category = strtolower($target->category);

        if (array_key_exists($category, $data)) {
            $data[$category] = $target->assigned_target ?? 0;
        }
    }

        $data['total_assigned'] = array_sum($data);

        return response()->json([
            'status' => 200,
            'message' => 'Assigned targets retrieved successfully',
            'data' => $data,
        ]);
    }

    public function assignTargets(Request $request)
{
    $user = Auth::user();

    $branch = $user->branch;

    if (!$branch) {
        return response()->json([
            'status' => 404,
            'message' => 'Branch not found'
        ],404);
    }

    $request->validate([
        'month' => 'required',
        'year' => 'required',
        'targets' => 'required|array'
    ]);

    foreach ($request->targets as $staff) {

        foreach ([
            'garments',
            'unstitched',
            'accessories'
        ] as $category) {

            $assignedTarget = AssignedTarget::where([
            'user_id' => $staff['sale_staff_id'],
            'month' => $request->month,
            'year' => $request->year,
            'category' => $category
        ])->first();

        if ($assignedTarget) {

            // If already approved, don't allow update
            if ($assignedTarget->status == 'approved') {

                return response()->json([
                    'status' => 400,
                    'message' => 'Approved by HOD - '.$assignedTarget->month.' '.$assignedTarget->year.''
                ]);

            }

            // Pending -> update target
            $assignedTarget->update([
                'target' => $staff[$category],
                'branch_manager_id' => $user->id,
                'branch_id' => $branch->id,
                'status' => 'pending'
            ]);

        } else {

            // First time assignment
            AssignedTarget::create([

                'user_id' => $staff['sale_staff_id'],
                'category' => $category,
                'target' => $staff[$category],
                'month' => $request->month,
                'year' => $request->year,
                'branch_manager_id' => $user->id,
                'branch_id' => $branch->id,
                'status' => 'pending'

            ]);

        }

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Notify Admin
    |--------------------------------------------------------------------------
    */

    Notification::create([
    'user_type' => 'admin',
    'title' => 'Monthly Targets Approval',
    'description' => $user->name . ' has assigned monthly targets for approval.',
    'is_read' => 0
    ]);

    return response()->json([

        'status' => 200,

        'message' => 'Targets submitted successfully and waiting for HOD approval.'

    ]);
}

}
