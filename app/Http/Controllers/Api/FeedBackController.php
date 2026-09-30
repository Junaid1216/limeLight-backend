<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\BranchManager;
use App\Models\FeedBack;
use App\Models\SaleStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedBackController extends Controller
{
    /**
     * Shared endpoint for Sale Staff and Branch Manager.
     * user_type is set from the authenticated user.
     */
    public function stafffeedback(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if ($user instanceof SaleStaff) {
            $userType = 'sale_staff';
        } elseif ($user instanceof BranchManager) {
            $userType = 'branch_manager';
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Only sales staff or branch manager can submit this feedback',
            ], 403);
        }

        $user->loadMissing('branch');

        $feedback = FeedBack::create([
            'user_type' => $userType,
            'code' => $request->code ?: $user->employee_id,
            'name' => $request->name ?: $user->name,
            'branch_name' => $request->branch_name ?: optional($user->branch)->name,
            'feedback' => $request->feedback,
        ]);

        return response()->json([
            'status' => '200',
            'message' => 'Feedback submitted successfully',
            'data' => $feedback,
        ]);
    }

    /**
     * Alias kept for older app builds — same as stafffeedback for Branch Manager.
     */
    public function managerfeedback(Request $request)
    {
        return $this->stafffeedback($request);
    }

    public function asmfeedback(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (!($user instanceof AreaSaleManager)) {
            return response()->json([
                'status' => 403,
                'message' => 'Only ASM can submit this feedback',
            ], 403);
        }

        $user->loadMissing('region');

        $feedback = FeedBack::create([
            'user_type' => 'area_sale_manager',
            'code' => $request->code ?: ($user->employee_id ?? null),
            'name' => $request->name ?: $user->name,
            'region_name' => $request->region_name ?: optional($user->region)->name,
            'feedback' => $request->feedback,
        ]);

        return response()->json([
            'status' => '200',
            'message' => 'Feedback submitted successfully',
            'data' => $feedback,
        ]);
    }
}
