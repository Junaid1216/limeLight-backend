<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Target;
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

}
