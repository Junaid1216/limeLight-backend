<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AreaSaleManager;
use App\Models\BranchManager;
use App\Models\SaleStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $role = $this->resolveRole($user);

        if (!$role) {
            return response()->json([
                'status' => 403,
                'message' => 'Unsupported user role',
            ], 403);
        }

        $category = $request->get('category'); // all | hr | performance | promotions

        $query = Announcement::where('status', 1)
            ->whereJsonContains('roles', $role)
            ->latest('id');

        if ($category && $category !== 'all') {
            $query->where('category', strtolower($category));
        }

        $announcements = $query->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'category' => $item->category,
                'category_label' => $item->category_label,
                'title' => $item->title,
                'description' => $item->description,
                'date' => $item->created_at->format('d-m-Y'),
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'Announcements fetched successfully',
            'data' => [
                'filters' => [
                    ['key' => 'all', 'label' => 'All'],
                    ['key' => 'hr', 'label' => 'HR'],
                    ['key' => 'performance', 'label' => 'Performance'],
                    ['key' => 'promotions', 'label' => 'Promotions'],
                ],
                'announcements' => $announcements,
            ],
        ]);
    }

    public function show($id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $role = $this->resolveRole($user);

        $announcement = Announcement::where('id', $id)
            ->where('status', 1)
            ->whereJsonContains('roles', $role)
            ->first();

        if (!$announcement) {
            return response()->json([
                'status' => 404,
                'message' => 'Announcement not found',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Announcement details',
            'data' => [
                'id' => $announcement->id,
                'category' => $announcement->category,
                'category_label' => $announcement->category_label,
                'title' => $announcement->title,
                'description' => $announcement->description,
            ],
        ]);
    }

    private function resolveRole($user): ?string
    {
        if ($user instanceof AreaSaleManager) {
            return 'asm';
        }

        if ($user instanceof BranchManager) {
            return 'branch_manager';
        }

        if ($user instanceof SaleStaff) {
            return 'sales_staff';
        }

        return null;
    }
}
