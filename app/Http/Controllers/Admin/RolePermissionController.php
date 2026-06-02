<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\SideMenue;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RolePermissionController extends Controller
{
    //

   public function index()
{
    $roles = Role::all();
    $permissions = Permission::all(); // Each permission should have: role_id, side_menue_id, permission_type
    $sideMenus = SideMenue::all();

    return view('admin.rolepermission.index', compact('roles', 'permissions', 'sideMenus'));
}





}
