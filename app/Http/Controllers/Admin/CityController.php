<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
     public function index()
    {
        $cities = City::latest()->get();

        return view('admin.city.index', compact('cities'));
    }

    public function create()
    {
        return view('admin.city.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:cities,name'
        ]);

        City::create([
            'name' => $request->name
        ]);

        return redirect()
            ->route('city.index')
            ->with('success', 'City Created Successfully');
    }

    public function edit($id)
    {
        $city = City::findOrFail($id);

        return view('admin.city.edit', compact('city'));
    }

    public function update(Request $request, $id)
    {
        $city = City::findOrFail($id);

        $request->validate([
            'name' => 'required|unique:cities,name,' . $id
        ]);

        $city->update([
            'name' => $request->name
        ]);

        return redirect()
            ->route('city.index')
            ->with('success', 'City Updated Successfully');
    }

    public function delete($id)
    {
        $city = City::findOrFail($id);

        $city->delete();

        return back()->with('success', 'City Deleted Successfully');
    }
}
