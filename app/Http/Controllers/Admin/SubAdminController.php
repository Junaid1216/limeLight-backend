<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\SideMenu;
use App\Models\SubAdmin;
use Illuminate\Http\Request;
use App\Models\SubAdminPermission;
use App\Models\UserRolePermission;
use App\Mail\SubAdminLoginPassword;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;


class SubAdminController extends Controller
{
    public function index()
    {
        $subAdmins = SubAdmin::with(['roles.rolePermissions.sideMenue'])
                     ->orderBy('status', 'desc')
                     ->latest()
                     ->get();
        $sideMenus = SideMenu::all();

         $sideMenuPermissions = collect();

    // ✅ Check if user is not admin (normal subadmin)
    if (!Auth::guard('admin')->check()) {
        $user =Auth::guard('subadmin')->user()->load('roles');


        // ✅ 1. Get role_id of subadmin
        $roleId = $user->role_id;

        // ✅ 2. Get all permissions assigned to this role
        $permissions = UserRolePermission::with(['permission', 'sideMenue'])
            ->where('role_id', $roleId)
            ->get();

        // ✅ 3. Group permissions by side menu
        $sideMenuPermissions = $permissions->groupBy('sideMenue.name')->map(function ($items) {
            return $items->pluck('permission.name'); // ['view', 'create']
        });
    }
        
        return view('admin.subadmin.index', compact('subAdmins', 'sideMenus' , 'sideMenuPermissions'));
    }

    public function create()
    {
        $sideMenus = SideMenu::all();
        $roles = Role::all();
        // return $roles;
        return view('admin.subadmin.create', compact('sideMenus', 'roles'));
    }


  
    public function store(Request $request)
    {

        // return $request->all();

        $validator = Validator::make($request->all(), [

            'name' => 'required|string|max:255',

            'email' => [

                'required',

                'email',

                'regex:/^[\w\.\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z]{2,6}$/',

                'unique:sub_admins,email',

            ],

            'role' => 'required|exists:roles,id',

            'image' => 'nullable|image|max:2048',

            'password' => 'nullable|min:6',

        ]);

        if ($validator->fails()) {

            return redirect()->back()
                ->withErrors($validator) // Pass validation errors
                ->withInput();

        }

        // If validation passes

        $validatedData = $validator->validated();

        if ($request->hasFile('image')) {

            $file = $request->file('image');

            $filename = time().'.'.$file->getClientOriginalExtension();

            $file->move(public_path('admin/assets/images/users/'), $filename);

            $image = 'public/admin/assets/images/users/'.$filename;

        } else {

            $image = 'public/admin/assets/images/avator.png';

        }

        // $password = random_int(10000000, 99999999);

        $password = '12345678'; // Default password

        $subAdmin = SubAdmin::create([

            'name' => $request->name,

            'email' => $request->email,

            'password' => bcrypt($password),

            'status' => $request->status ?? 1,

            'image' => $image,

        ]);

        // Attach role to pivot table

        $subAdmin->roles()->attach($request->role);

        return redirect()->route('subadmin.index')->with(['success' => 'Sub-Admin Created Successfully']);

    }

public function edit($id)
{
    $subAdmin = SubAdmin::findOrFail($id);
    $roles = Role::all();

    // ✅ Get only one role ID (assumes one role per subadmin)
    $currentRoleId = $subAdmin->roles->pluck('id')->first();

    return view('admin.subadmin.edit', compact('subAdmin', 'roles', 'currentRoleId'));
}



    public function update(Request $request, $id)
    {
     $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => [
            'required',
            'email',
            'regex:/^[\w\.\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z]{2,6}$/', // Ensure email is unique
        ],
      
        'image' => 'nullable|image|max:2048',
        'password' => 'nullable|min:6'
    ]);

    if ($validator->fails()) {
        return redirect()->back()
                    ->withErrors($validator) // Pass validation errors
                    ->withInput();
    }

// Only reached if validation passes
$validatedData = $validator->validated();

        $subAdmin = SubAdmin::findOrFail($id);

        $image = $subAdmin->image;

        if ($request->hasFile('image')) {
            $destination = 'public/admin/assets/images/users/' . $subAdmin->image;
            if (File::exists($destination)) {
                File::delete($destination);
            }

            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extension;
            $file->move('public/admin/assets/images/users', $filename);
            $image = 'public/admin/assets/images/users/' . $filename;
            $subAdmin->image = $image;
        }

		$password = '12345678';

        $subAdmin->update([
            'name' => $request->name,
            'email' => $request->email,
        //  'plain_password' => $password,
            'image' => $image,
            'password' => bcrypt($password),
        ]);

         // Single role update
        $subAdmin->roles()->sync([$request->role]);

        return redirect()->route('subadmin.index')->with('success', 'Sub-Admin Updated Successfully');
    }

    public function destroy($id)
    {
        // return $id;
        SubAdmin::destroy($id);
        return redirect()->route('subadmin.index')->with(['message' => 'Sub-Admin Deleted Successfully']);
    }

    public function updatePermissions(Request $request, $id)
    {
        $data = $request->validate([
            'sub_admin_id' => 'required',
            'side_menu_id' => 'nullable',
        ]);

        // dd($request);
        $permissions = [];
        if (!empty($data['side_menu_id'])) {
            foreach ($data['side_menu_id'] as $sideMenuId => $permissionArray) {
                foreach ($permissionArray as $permission) {
                    $permissions[] = [
                        'sub_admin_id' => $data['sub_admin_id'],
                        'side_menu_id' => $sideMenuId,
                        'permissions' => $permission, // Store each permission separately
                    ];
                }
            }
        }
        
        SubAdminPermission::where('sub_admin_id', $id)->delete();

        SubAdminPermission::insert($permissions);

        return redirect()->route('subadmin.index')->with('message', 'Permissions Updated Successfully');
    }

    public function StatusChange(Request $request)
    {
        $subAdmin = SubAdmin::find($request->id);
        $subAdmin->update([
            'status' => $request->status
        ]);
        return response()->json(['success' => true]);
    }


    public function toggleStatus(Request $request)
{
    $subAdmin = SubAdmin::findOrFail($request->id);
    $subAdmin->status = $request->status;
    $subAdmin->save();

    return response()->json([
        'success' => true,
        'message' => $request->status ? 'Status Activated Successfully' : 'Status Deactivated Successfully',
    ]);
}


}
