<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedBack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FeedBackController extends Controller
{
    public function index(Request $request)
    {
        $type = strtolower((string) $request->get('type', 'all'));

        $query = FeedBack::query()->latest();

        if ($type === 'staff') {
            $query->where(function ($q) {
                $q->where('user_type', 'sale_staff')
                    ->orWhere(function ($q2) {
                        $q2->whereNull('user_type')
                            ->whereNotNull('branch_name')
                            ->where('branch_name', '!=', '')
                            ->where(function ($q3) {
                                $q3->whereNull('region_name')->orWhere('region_name', '');
                            });
                    });
            });
        } elseif ($type === 'branch_manager') {
            $query->where('user_type', 'branch_manager');
        } elseif ($type === 'asm') {
            $query->where(function ($q) {
                $q->where('user_type', 'area_sale_manager')
                    ->orWhere(function ($q2) {
                        $q2->whereNull('user_type')
                            ->whereNotNull('region_name')
                            ->where('region_name', '!=', '');
                    });
            });
        }

        $feedbacks = $query->get();

        // Opening Feedback marks all as read so the sidebar counter clears
        if (Schema::hasColumn('feed_backs', 'is_read')) {
            FeedBack::where('is_read', 0)->update(['is_read' => 1]);
        }

        return view('admin.feedback.index', compact('feedbacks', 'type'));
    }

    public function unreadCount()
    {
        $count = 0;

        if (Schema::hasTable('feed_backs') && Schema::hasColumn('feed_backs', 'is_read')) {
            $count = FeedBack::where('is_read', 0)->count();
        }

        return response()->json([
            'status' => 200,
            'count' => $count,
        ]);
    }

    public function delete($id)
    {
        $feedback = FeedBack::findOrFail($id);
        $feedback->delete();

        return redirect()->route('feedback.index')->with('success', 'Feedback Deleted Successfully');
    }
}
