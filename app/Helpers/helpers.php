<?php

use App\Models\Role;
use App\Models\Permission;
use App\Models\SideMenue;
use App\Models\UserRolePermission;
use App\Models\SideMenuHasPermission;

if (!function_exists('getPermissionsData')) {
    function getPermissionsData($id)
    {
        $roles = Role::find($id);
        $permissions = Permission::all(); 
        $sideMenus = SideMenue::all();

        $existingPermissions = UserRolePermission::where('role_id', $id)
            ->with(['permission', 'sideMenue'])
            ->get()
            ->toArray();

        $sideMenuPermissions = SideMenuHasPermission::with('permission')
            ->get()
            ->groupBy('side_menu_id');

        return [
            'roles' => $roles,
            'permissions' => $permissions,
            'sideMenus' => $sideMenus,
            'existingPermissions' => $existingPermissions,
            'sideMenuPermissions' => $sideMenuPermissions,
        ];
    }
}
