<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\SideMenu;
use Illuminate\Http\Request;
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

        if ($admin) {
            return $next($request);
        }

        if (!$subadmin) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $sideMenuName = trim((string) $sideMenuName);
        $permissionType = trim((string) $permissionType);

        $sidemenu = SideMenu::where('name', $sideMenuName)->first();
        $permission = Permission::where('name', $permissionType)->first();

        if (!$sidemenu || !$permission) {
            abort(403, 'Invalid side menu name.');
        }

        $role = $subadmin->roles()->first();

        if (!$role) {
            abort(403, 'No role assigned to subadmin.');
        }

        $hasPermission = UserRolePermission::where('role_id', $role->id)
            ->where('side_menu_id', $sidemenu->id)
            ->where('permission_id', $permission->id)
            ->exists();

        if (!$hasPermission) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
