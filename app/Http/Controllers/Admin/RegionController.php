<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function index()
    {
        $regions = Region::latest()->get();
        return view('admin.region.index', compact('regions'));
    }

    public function create()
    {
        return view('admin.region.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:regions,name',
            'code' => 'unique:regions,code',
        ]);

        Region::create([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return redirect()->route('region.index')->with('success', 'Region Created Successfully');
    }

    public function edit($id)
    {
        $region = Region::findOrFail($id);
        return view('admin.region.edit', compact('region'));
    }

    public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required|unique:regions,name,' . $id,
        'code' => 'unique:regions,code,' . $id,
    ]);

    // Pehle region ko find karo
    $region = Region::findOrFail($id);

    // Update fields
    $region->name = $request->name;
    $region->code = $request->code;

    $region->save();

    return redirect('/admin/region')->with('success', 'Region Updated Successfully');
}

 public function delete($id) {
        $region = Region::find($id);
        if ($region) {
            $region->delete();
            return redirect('/admin/region')->with('success', 'Region Deleted Successfully');
        } else {
            return redirect('/admin/region')->with('error', 'Region Not Found');
        }
    }
}
