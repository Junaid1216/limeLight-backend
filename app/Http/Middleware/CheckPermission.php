<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\SideMenu;
use Illuminate\Http\Request;
use App\Models\RolePermission;
use App\Models\Permission;
use App\Models\UserRolePermission;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $sideMenuName
     * @param  string  $permissionType
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $sideMenuName, $permissionType)
    {
        $subadmin = Auth::guard('subadmin')->user();
        $admin = Auth::guard('admin')->user();

        // Allow access to admin always
        if ($admin) {
            return $next($request);
        }

        // If not logged in as subadmin
        if (!$subadmin) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        

        // Check if sidemenu exists by name
        $sidemenu = SideMenu::where('name', $sideMenuName)->first();
        $permission = Permission::where('name', $permissionType)->first();


        if (!$sidemenu) {
            abort(403, 'Invalid side menu name.');
        }

       $role = $subadmin->roles->first(); 

    if (!$role) {
    abort(403, 'No role assigned to subadmin.');
}

        // Check if the role has the permission for the given side menu
        $hasPermission = UserRolePermission::where('role_id', $role->id)
            ->where('side_menue_id', $sidemenu->id) // ✅ Corrected column name
            ->where('permission_id', $permission->id)
            ->exists();

       
// dd([
//     'role_id' => $role->id,
//     'side_menue_id' => $sidemenu->id,
//     'permission' => $permissionType,
// ]);


        // Check if the role has the permission for the given side menu
        if (!$hasPermission) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
