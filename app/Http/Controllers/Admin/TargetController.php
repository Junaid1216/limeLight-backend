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
        return view('admin.target.create', compact('branchManagers', 'branches'));
    }
    public function store(Request $request)
{
    $request->validate([
        // 'branch_manager_id' => 'required|exists:branch_managers,id',
        'month' => 'required',
        'category' => 'required',
        'monthly_target' => 'required|numeric',
        'week_1' => 'required|numeric',
        'week_2' => 'required|numeric',
        'week_3' => 'required|numeric',
        'week_4' => 'required|numeric',
        'designation_id' => 'required',
        'branch_id' => 'required',
    ],
    [
        'designation_id.required' => 'The Designation field is required.',
        'branch_id.required' => 'The Branch field is required.',
    ]);


    Target::create([
        // 'branch_manager_id' => $request->branch_manager_id,
        'month' => $request->month,
        'year' => $request->year,
        'category' => $request->category,
        'monthly_target' => $request->monthly_target,
        'week_1' => $request->week_1,
        'week_2' => $request->week_2,
        'week_3' => $request->week_3,
        'week_4' => $request->week_4,
        'designation_id' => $request->designation_id,
        'branch_id' => $request->branch_id,
    ]);

    return redirect()->route('target.index')->with('success', 'Target Created Successfully');
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
        // 'branch_manager_id' => 'required|exists:branch_managers,id',
        'month' => 'required',
        'category' => 'required',
        'monthly_target' => 'required|numeric',
        'week_1' => 'required|numeric',
        'week_2' => 'required|numeric',
        'week_3' => 'required|numeric',
        'week_4' => 'required|numeric',
        'designation_id' => 'required',
        'branch_id' => 'required',
    ],
    [
        'designation_id.required' => 'The Designation field is required.',
        'branch_id.required' => 'The Branch field is required.',
    ]);

    $target = Target::findOrFail($id);
    $target->update([
        // 'branch_manager_id' => $request->branch_manager_id,
        'month' => $request->month,
        'year' => $request->year,
        'category' => $request->category,
        'monthly_target' => $request->monthly_target,
        'week_1' => $request->week_1,
        'week_2' => $request->week_2,
        'week_3' => $request->week_3,
        'week_4' => $request->week_4,
        'designation_id' => $request->designation_id,
        'branch_id' => $request->branch_id,
    ]);

    return redirect()->route('target.index')->with('success', 'Target Updated Successfully');
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
