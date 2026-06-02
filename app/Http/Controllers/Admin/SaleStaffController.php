<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\BranchManager;
use App\Models\Designation;
use App\Models\SaleStaff;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SaleStaffController extends Controller
{
    public function index()
    {
        $salestaff = SaleStaff::latest()->get();
        return view('salestaff.index', compact('salestaff'));
    }

    public function create()
    {
        $designations = Designation::all();
        return view('salestaff.create', compact('designations'));
    }

    public function store(Request $request)
    {
         $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:sale_staff,email',
            'designation_id' => 'required',
            // 'branch_id' => 'required',
            'employee_id' => [
            'required',
            Rule::unique('sale_staff', 'employee_id'),

            function ($attribute, $value, $fail) {

                $existsInASM = \App\Models\AreaSaleManager::where('employee_id', $value)->exists();
                $existsInBM = \App\Models\BranchManager::where('employee_id', $value)->exists();

                if ($existsInASM || $existsInBM) {
                    $fail('The employee id has already been taken.');
                }
            }
        ],
        ],[
            'designation_id.required' => 'The designation field is required.',
        ]);

         do {

        $employeeId = 'SS' . rand(10000, 99999);

        } while (SaleStaff::where('employee_id', $employeeId)->exists());

         $password = '12345678';

        SaleStaff::create([
            'employee_id' => $request->employee_id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($password),
            'designation_id' => $request->designation_id,
            // 'branch_id' => $request->branch_id,
        ]);

        return redirect()->route('sale.staff.index')->with('success', 'Sales Staff Created Successfully');
    }

    public function edit($id)
    {
        $salestaff = SaleStaff::findOrFail($id);
        // $branches = Branch::all();
        $designations = Designation::all();
        return view('salestaff.edit', compact('salestaff', 'designations'));
    }

    public function update(Request $request, $id)
    {
        $salestaff = SaleStaff::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:sale_staff,email,' . $salestaff->id,
            'designation_id' => 'required',
            // 'branch_id' => 'required',
             'employee_id' => [
                'required',
                Rule::unique('sale_staff', 'employee_id')->ignore($salestaff->id),

                function ($attribute, $value, $fail) {

                    $existsInASM = \App\Models\AreaSaleManager::where('employee_id', $value)->exists();
                    $existsInBM = \App\Models\BranchManager::where('employee_id', $value)->exists();

                    if ($existsInASM || $existsInBM) {
                        $fail('The employee id has already been taken.');
                    }
                }
            ],
             
        ],[
            'designation_id.required' => 'The designation field is required.',
        ]);

        $salestaff->update([
            'employee_id' => $request->employee_id,
            'name' => $request->name,
            'email' => $request->email,
            'designation_id' => $request->designation_id,
            // 'branch_id' => $request->branch_id,
        ]);

        return redirect()->route('sale.staff.index')->with('success', 'Sales Staff Updated Successfully');
    }

        public function destroy($id)
        {
            $salestaff = SaleStaff::findOrFail($id);
            $salestaff->delete();
    
            return redirect()->route('sale.staff.index')->with('success', 'Sales Staff Deleted Successfully');
        }
}
