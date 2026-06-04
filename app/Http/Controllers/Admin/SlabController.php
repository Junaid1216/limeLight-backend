<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slab;
use Illuminate\Http\Request;

class SlabController extends Controller
{
    public function index()
{
    $incentives = Slab::orderBy('slab_name')->get();

    return view('admin.slab.index',compact('incentives')
    );
}

public function store(Request $request)
{
    foreach ($request->slab_name as $key => $slab) {

        Slab::updateOrCreate(
            [
                'slab_name' => $slab
            ],
            [
                'from_amount' => $request->from_amount[$key],
                'to_amount' => $request->to_amount[$key],
                'incentive_amount' => $request->incentive_amount[$key],
            ]
        );
    }

    return back()->with('success','Slabs Saved Successfully');
}
}
