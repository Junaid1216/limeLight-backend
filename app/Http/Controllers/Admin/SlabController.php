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

        return view('admin.slab.index', compact('incentives'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'slab_name' => 'required|array|min:1',
            'slab_name.*' => 'required|string|max:50',
            'from_amount' => 'required|array',
            'from_amount.*' => 'required|numeric|min:0',
            'to_amount' => 'required|array',
            'to_amount.*' => 'required|numeric|min:0',
            'incentive_amount' => 'required|array',
            'incentive_amount.*' => 'required|numeric|min:0',
            'slab_id' => 'nullable|array',
        ]);

        $keptIds = [];

        foreach ($request->slab_name as $key => $slabName) {
            $slabName = strtoupper(trim($slabName));
            $id = $request->slab_id[$key] ?? null;

            $data = [
                'slab_name' => $slabName,
                'from_amount' => $request->from_amount[$key],
                'to_amount' => $request->to_amount[$key],
                'incentive_amount' => $request->incentive_amount[$key],
            ];

            if ($id) {
                $slab = Slab::find($id);
                if ($slab) {
                    $slab->update($data);
                    $keptIds[] = $slab->id;
                    continue;
                }
            }

            $slab = Slab::updateOrCreate(
                ['slab_name' => $slabName],
                $data
            );

            $keptIds[] = $slab->id;
        }

        return back()->with('success', 'Slabs Saved Successfully');
    }

    public function delete($id)
    {
        $slab = Slab::find($id);

        if ($slab) {
            $slab->delete();
        }

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Slab deleted successfully',
            ]);
        }

        return back()->with('success', 'Slab Deleted Successfully');
    }
}
