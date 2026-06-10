<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\BranchManager;
use App\Models\Designation;
use App\Models\Region;
use App\Models\SaleStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class ASMController extends Controller
{
    public function index()
    {
        
        $asms = AreaSaleManager::with('region')->latest()->get();
        return view('admin.asm.index', compact('asms'));
    }

    public function create()
    {
        $regions = Region::all();
        $designations = Designation::all();
        return view('admin.asm.create', compact('regions', 'designations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:area_sale_managers,email',
            'password' => 'nullable|min:6',
            'region_id' => 'required|unique:area_sale_managers,region_id',
            'designation_id' => 'required',
            'image' => 'required|max:2048',
            'employee_id' => [
            'required',
            Rule::unique('area_sale_managers', 'employee_id'),

            function ($attribute, $value, $fail) {

                $existsInBM = \App\Models\BranchManager::where('employee_id', $value)->exists();
                $existsInSS = \App\Models\SaleStaff::where('employee_id', $value)->exists();

                if ($existsInBM || $existsInSS) {
                    $fail('The employee id has already been taken.');
                }
            }
        ],
        ],
        [
            'region_id.unique' => 'This region has already been taken.',
            'designation_id.required' => 'The designation field is required.',
            'image.required' => 'The image field is required.',
            'image.max' => 'The image must not be greater than 2MB.',
        ]);

        $password = '12345678';

         if ($request->hasFile('image')) {

            $file = $request->file('image');

            $filename = time().'.'.$file->getClientOriginalExtension();

            $file->move(public_path('admin/assets/images/users/'), $filename);

            $image = 'public/admin/assets/images/users/'.$filename;

        } else {

            $image = 'public/admin/assets/images/avator.png';

        }

        AreaSaleManager::create([
            'employee_id' => $request->employee_id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($password),
            'region_id' => $request->region_id,
            'designation_id' => $request->designation_id,
            'image' => $image,
        ]);

        return redirect()->route('asm.index')->with('success', 'Area Sale Manager Created Successfully');
    }

    public function edit($id)
    {
        $asm = AreaSaleManager::findOrFail($id);
        $regions = Region::all();
        $designations = Designation::all();
        return view('admin.asm.edit', compact('asm', 'regions','designations'));
    }

    public function update(Request $request, $id)
    {
        $asm = AreaSaleManager::findOrFail($id);
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:area_sale_managers,email,' . $id,
            'password' => 'nullable|min:6',
            'region_id' => 'required|unique:area_sale_managers,region_id,' . $id,
            'designation_id' => 'required',
            'image' => 'required|max:2048',
            'employee_id' => [
                'required',
                Rule::unique('area_sale_managers', 'employee_id')->ignore($asm->id),

                function ($attribute, $value, $fail) {

                    $existsInBM = \App\Models\BranchManager::where('employee_id', $value)->exists();
                    $existsInSS = \App\Models\SaleStaff::where('employee_id', $value)->exists();

                    if ($existsInBM || $existsInSS) {
                        $fail('The employee id has already been taken.');
                    }
                }
            ],
        ],
        [
            'region_id.unique' => 'This region has already been taken.',
            'designation_id.required' => 'The designation field is required.',
            'image.required' => 'The image field is required.',
            'image.max' => 'The image must not be greater than 2MB.',
        ]);

        
        $password = $request->password ? bcrypt($request->password) : $asm->password;

        $image = $asm->image;

         if ($request->hasFile('image')) {
            $destination = 'public/admin/assets/images/users/' . $asm->image;
            if (File::exists($destination)) {
                File::delete($destination);
            }

            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extension;
            $file->move('public/admin/assets/images/users', $filename);
            $image = 'public/admin/assets/images/users/' . $filename;
            $asm->image = $image;
        }

        $asm->update([
            'employee_id' => $request->employee_id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => $password,
            'region_id' => $request->region_id,
            'designation_id' => $request->designation_id,
            'image' => $image,
        ]);

        return redirect()->route('asm.index')->with('success', 'Area Sale Manager Updated Successfully');
    }

    public function delete($id)
    {
        $asm = AreaSaleManager::findOrFail($id);
        $asm->delete();

        return redirect()->route('asm.index')->with('success', 'Area Sale Manager Deleted Successfully');
    }
}
