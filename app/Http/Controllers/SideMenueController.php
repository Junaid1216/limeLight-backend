<?php

namespace App\Http\Controllers;

use App\Models\SideMenue;
use Illuminate\Http\Request;

class SideMenueController extends Controller
{
    //

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:side_menus',
        ]);

        $sideMenue = SideMenue::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Side menu created successfully!',
            'data' => $sideMenue
        ], 201);
    }
}
