<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    //

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions',
        ]);

        $permission = Permission::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Permission created successfully!',
            'data' => $permission
        ], 201);
    }
}
