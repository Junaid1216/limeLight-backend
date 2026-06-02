<?php

namespace App\Http\Controllers;
use App\Models\SideMenuHasPermission;
use Illuminate\Http\Request;

class SideMenuPermissionController extends Controller
{
    //

   public function assignPermissions(Request $request)
{

    // yah validate method  khbi tang krta ias liye agr post na hou data tou validate comment kr lai
    $request->validate([
        'side_menu_id' => 'required|exists:side_menus,id',
        'permission_id' => 'required|exists:permissions,id', // sirf ek ID
    ]);

    SideMenuHasPermission::create([
        'side_menu_id' => $request->side_menu_id,
        'permission_id' => $request->permission_id,
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Permission assigned successfully!',
    ]);
}
}
