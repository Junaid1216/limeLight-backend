<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\City;
use App\Models\Region;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::latest()->get();
        return view('admin.branch.index', compact('branches'));
    }

    public function create()
    {
        $cities = City::all();
        $regions = Region::all();
        return view('admin.branch.create', compact('cities', 'regions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'region' => 'required',
            'address' => 'nullable|string|max:255',
        ]);

        Branch::create([
            'name' => $request->name,
            'city' => $request->city,
            'region_id' => $request->region,
            'address' => $request->address,
        ]);

        return redirect()->route('branch.index')->with('success', 'Branch Created Successfully');
    }

    public function edit($id)
    {
        $branch = Branch::findOrFail($id);
        $cities = City::all();
        $regions = Region::all();
        return view('admin.branch.edit', compact('branch','cities', 'regions'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'region' => 'required',
            'address' => 'nullable|string|max:255',
        ]);

        $branch = Branch::findOrFail($id);
        $branch->update([
            'name' => $request->name,
            'city' => $request->city,
            'region_id' => $request->region,
            'address' => $request->address,
        ]);

        return redirect()->route('branch.index')->with('success', 'Branch Updated Successfully');
    }

    public function delete($id)
    {
        $branch = Branch::findOrFail($id);
        $branch->delete();

        return redirect()->route('branch.index')->with('success', 'Branch Deleted Successfully');
    }
}
