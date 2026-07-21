<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssignedTarget;
use App\Models\Branch;
use App\Models\BranchManager;
use App\Models\SaleStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignedTargetController extends Controller
{
    public function index(Request $request)
    {
        $months = AssignedTarget::whereNotNull('month')->distinct()->orderBy('month')->pluck('month');
        $years = AssignedTarget::whereNotNull('year')->distinct()->orderByDesc('year')->pluck('year');

        $defaultMonth = now()->format('F');
        $defaultYear = (string) now()->year;

        if ($months->isNotEmpty() && !$months->contains($defaultMonth)) {
            $months = $months->prepend($defaultMonth)->unique()->values();
        } elseif ($months->isEmpty()) {
            $months = collect([$defaultMonth]);
        }

        if ($years->isNotEmpty() && !$years->contains($defaultYear) && !$years->contains((int) $defaultYear)) {
            $years = $years->prepend($defaultYear)->unique()->values();
        } elseif ($years->isEmpty()) {
            $years = collect([$defaultYear]);
        }

        $status = $request->get('status', 'pending');
        $month = $request->get('month', $defaultMonth);
        $year = $request->get('year', $defaultYear);

        if (!in_array($status, ['pending', 'approved'], true)) {
            $status = 'pending';
        }

        if ($month === '' || $month === null) {
            $month = $defaultMonth;
        }

        if ($year === '' || $year === null) {
            $year = $defaultYear;
        }

        $groupsQuery = AssignedTarget::query()
            ->select(
                'branch_id',
                'branch_manager_id',
                'month',
                'year',
                DB::raw("CASE
                    WHEN SUM(CASE WHEN LOWER(COALESCE(status, 'pending')) = 'approved' THEN 0 ELSE 1 END) > 0
                    THEN 'pending'
                    ELSE 'approved'
                END as group_status"),
                DB::raw('MAX(updated_at) as last_updated'),
                DB::raw('COUNT(DISTINCT user_id) as staff_count')
            )
            ->whereNotNull('branch_id')
            ->whereNotNull('month')
            ->whereNotNull('year')
            ->where('month', $month)
            ->where('year', $year)
            ->groupBy('branch_id', 'branch_manager_id', 'month', 'year');

        $groups = $groupsQuery
            ->orderByRaw('MAX(updated_at) DESC')
            ->get()
            ->filter(function ($group) use ($status) {
                return strtolower((string) $group->group_status) === strtolower($status);
            })
            ->map(function ($group) {
                $branch = Branch::find($group->branch_id);
                $manager = BranchManager::with('designation')->find($group->branch_manager_id);

                $staffRows = $this->buildStaffRows(
                    $group->branch_id,
                    $group->branch_manager_id,
                    $group->month,
                    $group->year
                );

                return [
                    'branch_id' => $group->branch_id,
                    'branch_name' => optional($branch)->name ?? '-',
                    'branch_manager_id' => $group->branch_manager_id,
                    'branch_manager_name' => optional($manager)->name ?? '-',
                    'designation' => optional(optional($manager)->designation)->name ?? '-',
                    'month' => $group->month,
                    'year' => $group->year,
                    'status' => $group->group_status ?: 'pending',
                    'staff_count' => $group->staff_count,
                    'last_updated' => $group->last_updated,
                    'staff' => $staffRows,
                ];
            })
            ->values();

        return view('admin.assignedtarget.index', compact(
            'groups',
            'status',
            'month',
            'year',
            'months',
            'years'
        ));
    }

    public function approve(Request $request)
    {
        $request->validate([
            'branch_id' => 'required',
            'branch_manager_id' => 'required',
            'month' => 'required',
            'year' => 'required',
        ]);

        $updated = AssignedTarget::where('branch_id', $request->branch_id)
            ->where('branch_manager_id', $request->branch_manager_id)
            ->where('month', $request->month)
            ->where('year', $request->year)
            ->update([
                'status' => 'approved',
            ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Assigned targets approved successfully.',
                'updated' => $updated,
            ]);
        }

        return back()->with('success', 'Assigned targets approved successfully.');
    }

    private function buildStaffRows($branchId, $branchManagerId, $month, $year): array
    {
        $targets = AssignedTarget::where('branch_id', $branchId)
            ->where('branch_manager_id', $branchManagerId)
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $staffIds = $targets->pluck('user_id')->unique()->filter()->values();
        $staffMap = SaleStaff::whereIn('id', $staffIds)->get()->keyBy('id');

        $rows = [];

        foreach ($staffIds as $staffId) {
            $staff = $staffMap->get($staffId);
            $staffTargets = $targets->where('user_id', $staffId);

            $rows[] = [
                'staff_id' => $staffId,
                'name' => optional($staff)->name ?? ('Staff #' . $staffId),
                'initials' => $this->initials(optional($staff)->name ?? 'NA'),
                'garments' => (float) (optional($staffTargets->firstWhere('category', 'garments'))->target ?? 0),
                'unstitched' => (float) (optional($staffTargets->firstWhere('category', 'unstitched'))->target ?? 0),
                'accessories' => (float) (optional($staffTargets->firstWhere('category', 'accessories'))->target ?? 0),
            ];
        }

        return $rows;
    }

    private function initials(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'NA';
        }

        $parts = preg_split('/\s+/', $name);
        $first = strtoupper(substr($parts[0] ?? '', 0, 1));
        $second = strtoupper(substr($parts[1] ?? $parts[0] ?? '', 0, 1));

        return $first . $second;
    }
}
