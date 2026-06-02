<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
     public function index()
    {
        $designations = Designation::latest()->get();

        return view('admin.designation.index', compact('designations'));
    }

    public function create()
    {
        return view('admin.designation.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:designations,name'
        ]);

        Designation::create([
            'name' => $request->name
        ]);

        return redirect()
            ->route('designation.index')
            ->with('success', 'Designation Created Successfully');
    }

    public function edit($id)
    {
        $designation = Designation::findOrFail($id);

        return view('admin.designation.edit', compact('designation'));
    }

    public function update(Request $request, $id)
    {
        $designation = Designation::findOrFail($id);

        $request->validate([
            'name' => 'required|unique:designations,name,' . $id
        ]);

        $designation->update([
            'name' => $request->name
        ]);

        return redirect()
            ->route('designation.index')
            ->with('success', 'Designation Updated Successfully');
    }

    public function delete($id)
    {
        $designation = Designation::findOrFail($id);

        $designation->delete();

        return back()->with('success', 'Designation Deleted Successfully');
    }
}
