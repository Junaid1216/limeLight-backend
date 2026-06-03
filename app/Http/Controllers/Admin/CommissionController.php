<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function index()
    {
        $commissions = Commission::all();
        return view('admin.commission.index', compact('commissions'));
    }

    public function store(Request $request)
{
    $request->validate([

        'role' => 'required',

        'commission' => 'required|numeric',

    ]);

    Commission::create($request->all());

    return redirect()->route('commission.index')->with('success', 'Commission Added Successfully');

}

public function delete($id)
{
    $commission = Commission::findOrFail($id);
    $commission->delete();

    return redirect()->route('commission.index')->with('success', 'Commission Deleted Successfully');
}
}
