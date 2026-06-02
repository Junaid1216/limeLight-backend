<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\Branch;
use App\Models\BranchManager;
use App\Models\Region;
use App\Models\SaleStaff;
use Illuminate\Http\Request;

class HierarchyController extends Controller
{
    public function index()
{
    
    $asms = AreaSaleManager::all();
    $branchManagers = BranchManager::all();
    $saleStaff = SaleStaff::all();
    $regions = Region::all();
    $branches = Branch::all();
    $hierarchy = AreaSaleManager::with(['branchManagers.saleStaff','region','branchManagers.branch'])->whereHas('branchManagers')->get();

    return view('admin.hierarchy.index', compact('asms', 'branchManagers', 'saleStaff', 'regions', 'branches', 'hierarchy'));
}

public function getRegionAsms($id)
{
    $asms = AreaSaleManager::where('region_id', $id)->get();

    return response()->json($asms);
}

public function getBranchManagers($id)
{
    $branchManagers = BranchManager::where('branch_id', $id)
        ->whereNull('asm_id')
        ->get();

    return response()->json($branchManagers);
}

public function store(Request $request)
{
    $request->validate([
        'asm_id' => 'required',
    ]);

     AreaSaleManager::where('id', $request->asm_id)
        ->update([
            'region_id' => $request->region_id
        ]);


    // Assign ASM → Branch Managers
    if ($request->branch_managers) {
        foreach ($request->branch_managers as $bmId) {
            BranchManager::where('id', $bmId)
                ->update([
                    'asm_id' => $request->asm_id,
                    'branch_id' => $request->branch_id
                    ]);
        }
    }

    // Assign Staff → Branch Managers
    if ($request->staff) {
        foreach ($request->staff as $bmId => $staffIds) {

            foreach ($staffIds as $staffId) {
                SaleStaff::where('id', $staffId)
                    ->update([
                        'branch_manager_id' => $bmId]);
            }
        }
    }

    return back()->with('success', 'Hierarchy Saved Successfully');
}

public function getRegionBranches($regionId)
{
    $region = Region::find($regionId);

    if (!$region) {
        return response()->json([]);
    }

    $branches = Branch::where('city', $region->name)->get();

    return response()->json($branches);
}

public function removeAsm(Request $request)
{
    $asmId = $request->id;

    BranchManager::where('asm_id', $asmId)
        ->update(['asm_id' => null]);

    return response()->json(['success' => true]);
}
}
