<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\Branch;
use App\Models\BranchManager;
use App\Models\Designation;
use App\Models\SaleStaff;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchManagerController extends Controller
{
    public function index()
    {
        $branchmanagers = BranchManager::with('branch')->latest()->get();
        return view('branchmanager.index', compact('branchmanagers'));
    }

    public function create()
    {
        $branches = Branch::all();
        $designations = Designation::all();
        return view('branchmanager.create', compact('branches', 'designations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:branch_managers,email',
            'branch_id' => 'required',
            'designation_id' => 'required',
            'employee_id' => [
            'required',
            Rule::unique('branch_managers', 'employee_id'),

            function ($attribute, $value, $fail) {

                $existsInASM = \App\Models\AreaSaleManager::where('employee_id', $value)->exists();
                $existsInSS = \App\Models\SaleStaff::where('employee_id', $value)->exists();

                if ($existsInASM || $existsInSS) {
                    $fail('The employee id has already been taken.');
                }
            }
        ],
        ],
        [
            'designation_id.required' => 'The designation field is required.',
        ]);


         $password = '12345678';

        BranchManager::create([
            'employee_id' => $request->employee_id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($password),
            'branch_id' => $request->branch_id,
            'designation_id' => $request->designation_id,
        ]);

        return redirect()->route('branch.manager.index')->with('success', 'Branch Manager Created Successfully');
    }

    public function edit($id)
    {
        $branchmanager = BranchManager::findOrFail($id);
        $branches = Branch::all();
        $designations = Designation::all();
        return view('branchmanager.edit', compact('branchmanager', 'branches', 'designations'));
    }

    public function update(Request $request, $id)
    {
        $branchmanager = BranchManager::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:branch_managers,email,' . $id,
            'branch_id' => 'required',
            'designation_id' => 'required',
            'employee_id' => [
                'required',
                Rule::unique('branch_managers', 'employee_id')->ignore($branchmanager->id),

                function ($attribute, $value, $fail) {

                    $existsInASM = \App\Models\AreaSaleManager::where('employee_id', $value)->exists();
                    $existsInSS = \App\Models\SaleStaff::where('employee_id', $value)->exists();

                    if ($existsInASM || $existsInSS) {
                        $fail('The employee id has already been taken.');
                    }
                }
            ],
        ],
        [
            'designation_id.required' => 'The designation field is required.',
        ]);

        
        $branchmanager->update([
            'employee_id' => $request->employee_id,
            'name' => $request->name,
            'email' => $request->email,
            'branch_id' => $request->branch_id,
            'designation_id' => $request->designation_id,
        ]);

        return redirect()->route('branch.manager.index')->with('success', 'Branch Manager Updated Successfully');
    }

    public function delete($id)
    {
        $branchmanager = BranchManager::findOrFail($id);
        $branchmanager->delete();
        return redirect()->route('branch.manager.index')->with('success', 'Branch Manager Deleted Successfully');
    }
}
