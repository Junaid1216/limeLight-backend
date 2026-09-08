<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CommissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Designation;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function index()
    {
        $commissions = Commission::latest()->get();
        $designations = Designation::orderBy('name')
        ->get(['id', 'name']);
        return view('admin.commission.index', compact('commissions', 'designations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'role' => 'required',
            'commission' => 'required|numeric',
        ]);

        $rate = (float) $request->commission;

        Commission::updateOrCreate(
            [
                'role' => $request->role,
            ],
            [
                'commission' => $rate,
            ]
        );

        // Lock previous rate for past sales; new rate applies only from now
        CommissionHelper::recordChange($request->role, $rate);

        return redirect()->route('commission.index')->with('success', 'Commission Added Successfully');
    }

    public function delete($id)
    {
        $commission = Commission::findOrFail($id);

        CommissionHelper::closeRole($commission->role);
        $commission->delete();

        return redirect()->route('commission.index')->with('success', 'Commission Deleted Successfully');
    }
}
