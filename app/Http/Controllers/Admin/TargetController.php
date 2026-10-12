<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssignedTarget;
use App\Models\Branch;
use App\Models\BranchManager;
use App\Models\Designation;
use App\Models\Target;
use Illuminate\Http\Request;

class TargetController extends Controller
{
    public function index()
    {
        $targets = Target::with('branchManager')->latest()->get();
        return view('admin.target.index', compact('targets'));
    }

    public function create()
    {
        $branchManagers = BranchManager::all();
        $branches = Branch::all();
        $targets = Target::select('branch_id', 'month', 'year', 'category')->get();
        $branchOptions = $branches->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
            ];
        })->values();

        return view('admin.target.create', compact('branchManagers', 'branches', 'targets', 'branchOptions'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'month' => 'required',
            'year' => 'required',
            'categories' => 'required|array|min:1',
            'categories.*' => 'in:garments,unstitched,accessories',
            'targets' => 'required|array',
            'designation_id' => 'required',
            'branch_id' => 'required',
        ], [
            'designation_id.required' => 'The Designation field is required.',
            'branch_id.required' => 'The Branch field is required.',
            'categories.required' => 'Please select at least one category.',
        ]);

        $payloads = $this->validatedCategoryTargets($request);
        if ($payloads instanceof \Illuminate\Http\RedirectResponse) {
            return $payloads;
        }

        $duplicates = [];
        foreach ($payloads as $row) {
            if ($this->targetConflict($request->branch_id, $request->month, $request->year, $row['category'])) {
                $duplicates[] = ucfirst($row['category']);
            }
        }

        if (!empty($duplicates)) {
            return back()
                ->withInput()
                ->with('error', 'Target already exists for this Month, Branch and Category: ' . implode(', ', $duplicates) . '.');
        }

        foreach ($payloads as $row) {
            Target::create([
                'month' => $request->month,
                'year' => $request->year,
                'category' => $row['category'],
                'monthly_target' => $row['monthly_target'],
                'week_1' => $row['week_1'],
                'week_2' => $row['week_2'],
                'week_3' => $row['week_3'],
                'week_4' => $row['week_4'],
                'designation_id' => $request->designation_id,
                'branch_id' => $request->branch_id,
            ]);
        }

        $count = count($payloads);
        return redirect()->route('target.index')->with(
            'success',
            $count === 1 ? 'Target Created Successfully' : $count . ' Targets Created Successfully'
        );
    }

public function edit($id)
{
    $target = Target::findOrFail($id);
    // $branchManagers = BranchManager::all();
    $branches = Branch::all();
    return view('admin.target.edit', compact('target', 'branches'));
}

public function update(Request $request, $id)
{
    $request->validate([
        'month' => 'required',
        'year' => 'required',
        'categories' => 'required|array|min:1',
        'categories.*' => 'in:garments,unstitched,accessories',
        'targets' => 'required|array',
        'designation_id' => 'required',
        'branch_id' => 'required',
    ], [
        'designation_id.required' => 'The Designation field is required.',
        'branch_id.required' => 'The Branch field is required.',
        'categories.required' => 'Please select at least one category.',
    ]);

    $payloads = $this->validatedCategoryTargets($request);
    if ($payloads instanceof \Illuminate\Http\RedirectResponse) {
        return $payloads;
    }

    $target = Target::findOrFail($id);

    $duplicates = [];
    foreach ($payloads as $row) {
        if ($this->targetConflict($request->branch_id, $request->month, $request->year, $row['category'], $target->id)) {
            $duplicates[] = ucfirst($row['category']);
        }
    }

    if (!empty($duplicates)) {
        return back()
            ->withInput()
            ->with('error', 'Target already exists for this Month, Branch and Category: ' . implode(', ', $duplicates) . '.');
    }

    $created = 0;

    foreach ($payloads as $row) {
        if ($row['category'] === $target->category) {
            $target->update([
                'month' => $request->month,
                'year' => $request->year,
                'category' => $row['category'],
                'monthly_target' => $row['monthly_target'],
                'week_1' => $row['week_1'],
                'week_2' => $row['week_2'],
                'week_3' => $row['week_3'],
                'week_4' => $row['week_4'],
                'designation_id' => $request->designation_id,
                'branch_id' => $request->branch_id,
            ]);
            continue;
        }

        Target::create([
            'month' => $request->month,
            'year' => $request->year,
            'category' => $row['category'],
            'monthly_target' => $row['monthly_target'],
            'week_1' => $row['week_1'],
            'week_2' => $row['week_2'],
            'week_3' => $row['week_3'],
            'week_4' => $row['week_4'],
            'designation_id' => $request->designation_id,
            'branch_id' => $request->branch_id,
        ]);
        $created++;
    }

    $message = 'Target Updated Successfully';
    if ($created > 0) {
        $message .= ' (' . $created . ' additional categor' . ($created === 1 ? 'y' : 'ies') . ' added)';
    }

    return redirect()->route('target.index')->with('success', $message);
}

/**
 * @return array<int, array<string, mixed>>|\Illuminate\Http\RedirectResponse
 */
private function validatedCategoryTargets(Request $request)
{
    $rows = [];

    foreach ((array) $request->categories as $category) {
        $data = $request->input('targets.' . $category, []);

        $request->validate([
            "targets.$category.monthly_target" => 'required|numeric',
            "targets.$category.week_1" => 'required|numeric',
            "targets.$category.week_2" => 'required|numeric',
            "targets.$category.week_3" => 'required|numeric',
            "targets.$category.week_4" => 'required|numeric',
        ], [
            "targets.$category.monthly_target.required" => ucfirst($category) . ' monthly target is required.',
            "targets.$category.week_1.required" => ucfirst($category) . ' week 1 is required.',
            "targets.$category.week_2.required" => ucfirst($category) . ' week 2 is required.',
            "targets.$category.week_3.required" => ucfirst($category) . ' week 3 is required.',
            "targets.$category.week_4.required" => ucfirst($category) . ' week 4 is required.',
        ]);

        $weekSum = (float) ($data['week_1'] ?? 0)
            + (float) ($data['week_2'] ?? 0)
            + (float) ($data['week_3'] ?? 0)
            + (float) ($data['week_4'] ?? 0);

        if ($weekSum > 100) {
            return back()
                ->withInput()
                ->with('error', ucfirst($category) . ': weekly percentages cannot exceed 100%.');
        }

        $rows[] = [
            'category' => $category,
            'monthly_target' => $data['monthly_target'],
            'week_1' => $data['week_1'],
            'week_2' => $data['week_2'],
            'week_3' => $data['week_3'],
            'week_4' => $data['week_4'],
        ];
    }

    return $rows;
}

private function targetConflict($branchId, $month, $year, $category, $ignoreId = null): bool
{
    $query = Target::query()
        ->where('branch_id', $branchId)
        ->where('month', $month)
        ->where('year', $year)
        ->where('category', $category);

    if ($ignoreId) {
        $query->where('id', '!=', $ignoreId);
    }

    return $query->exists();
}

public function getBranchDesignations($branchId)
{
    $designations = \App\Models\BranchManager::with('designation')
        ->where('branch_id', $branchId)
        ->whereNotNull('designation_id')
        ->get()
        ->pluck('designation')
        ->unique('id')
        ->values();

    return response()->json($designations);
}

public function toggleStatus(Request $request)
{
    $target = Target::find($request->id);

    if (!$target) {
        return response()->json([
            'success' => false,
            'message' => 'Target not found'
        ]);
    }

    $target->toggle = $request->status;

    $saved = $target->save();


    return response()->json([
        'success' => $saved,
        'message' => $request->status
            ? 'Target Activated Successfully'
            : 'Target Deactivated Successfully'
    ]);
}
}
