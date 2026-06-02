<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\UserRolePermission;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    //

    public function Index()
    {
        $users = User::orderby('id', 'desc')->get();

        
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

        return view('users.index', compact('users' , 'sideMenuPermissions'));
    }

public function toggleStatus(Request $request)
{
    $user = User::find($request->id);
    
    if ($user) {
        $user->toggle = $request->status;
        $user->save();
        
        // If deactivating and reason provided
        if ($request->status == 0 && $request->reason) {
            // Save the reason (you might want to create a separate table for this)
            // $user->deactivation_reason = $request->reason;
            // $user->save();
            
            // Send email notification
            $this->sendDeactivationEmail($user, $request->reason);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'new_status' => $user->toggle ? 'Activated' : 'Deactivated'
        ]);
    }
    
    return response()->json([
        'success' => false,
        'message' => 'User not found'
    ], 404);
}

protected function sendDeactivationEmail($user, $reason)
{
    $data = [
        'name' => $user->name,
        'email' => $user->email,
        'reason' => $reason
    ];
    
    try {
        Mail::send('emails.user_deactivated', $data, function($message) use ($user) {
            $message->to($user->email, $user->name)
                    ->subject('Account Deactivation Notification');
        });
    } catch (\Exception $e) {
        \Log::error("Failed to send deactivation email: " . $e->getMessage());
    }
}


    public function createview() {
        return view('users.create');
    }

  public function create(Request $request)
{
    // 🔹 Validation
    $request->validate([
        'name' => 'required',
        'image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:2048',
        'email' => [
            'required',
            'email',
            'regex:/^[\w\.\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z]{2,6}$/'
        ],
        'phone' => [
            'required',
            'regex:/^\+92[0-9]{10}$/'
        ],
        'password' => 'required|min:6',
    ]);

    // 🔹 Default image path
    $imagePath = null;

    // 🔹 Check if image uploaded
    if ($request->hasFile('image')) {
        $file = $request->file('image');

        // unique name
        $imageName = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

        // Move the uploaded file
        $file->move(public_path('admin/assets/images/'), $imageName);

        // ✅ DB کیلئے relative path manually بنائیں
        $imagePath = 'public/admin/assets/images/'.$imageName;

	
    }

    // 🔹 Save user to DB
    User::create([
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'image' => $imagePath, // اگر null ہے تو null save ہوگا
        'password' => bcrypt($request->password),
    ]);

    return redirect()->route('user.index')->with('success', 'User created successfully');
}



    public function edit($id) {
        $user = User::find($id);
        return view('users.edit', compact('user'));
    }
    
public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required',
        'image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:2048',
        'email' => [
            'required',
            'email',
            'regex:/^[\w\.\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z]{2,6}$/'
        ],
        'phone' => [
            'required',
            'regex:/^\+92[0-9]{10}$/'
        ],
        'password' => 'nullable|min:6',
    ]);

    // Pehle user ko find karo
    $user = User::findOrFail($id);

    // Update fields
    $user->name = $request->name;
    $user->email = $request->email;
    $user->phone = $request->phone;

    // Agar new image upload hui hai to hi update karo
    if ($request->hasFile('image')) {
        $file = $request->file('image');

        // unique name
        $imageName = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

        // move to public/admin/assets/images
        $file->move(public_path('admin/assets/images/'), $imageName);

        // relative path (public نہ رکھو)
        $imagePath = 'public/admin/assets/images/'.$imageName;

        // assign new image path
        $user->image = $imagePath;
    }
    // else case nahi lagانا, پرانی image jesi ki jesi رہے گی

    // Agar password diya gaya hai toh update karo
    if ($request->filled('password')) {
        $user->password = bcrypt($request->password);
    }

    $user->save();

    return redirect('/admin/user')->with('success', 'User updated successfully');
}



    public function delete($id) {
        $user = User::find($id);
        if ($user) {
            $user->delete();
            return redirect('/admin/user')->with('success', 'User deleted successfully');
        } else {
            return redirect('/user')->with('error', 'User not found');
        }
    }


//    public function trashed()
// {
//     $users = User::onlyTrashed()->get();
//     return view('users.trashed', compact('users'));
// }

// // Restore user
// public function restore($id)
// {
//     User::withTrashed()->findOrFail($id)->restore();
//     return back()->with('success', 'User restored.');
// }

// // Force delete
// public function forceDelete($id)
// {
//     User::withTrashed()->findOrFail($id)->forceDelete();
//     return back()->with('success', 'User permanently deleted.');
// }


    
}
