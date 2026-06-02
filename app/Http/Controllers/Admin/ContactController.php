<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContactUs;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\UserRolePermission;


class ContactController extends Controller
{
    //
    public function Index() {
        $contacts = ContactUs::orderby('id', 'desc')->get();

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

        

        return view('admin.contact.index', compact('contacts' , 'sideMenuPermissions'));
    }

    public function createview() {
       
        
        return view('admin.contact.create');
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
        ]);
    
        // If validation fails
        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }
    
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        // Save data
        ContactUs::create([
            'email' => $request->email,
            'phone' => $request->phone,
        ]);
    
        // Success response
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Contact information saved successfully.',
            ]);
        }
    
        // return redirect()->route('contact.index')->with('success', 'Contact added successfully.');
    }
    

 public function updateview($id) {
    $find = ContactUs::find($id);
    

    return view('admin.contact.edit', compact('find'));

}


public function update(Request $request, $id)
    {
        
        $contact = ContactUs::find($id);

        // ✅ Update values
        $contact->email   = $request->input('email');
        $contact->phone   = $request->input('phone');
        // $contact->address = $request->input('address');

        // 💾 Save to database
        $contact->save();

        // 🔙 Redirect or respond
        return redirect('admin/admin/contact-us')->with('success', 'Contact-Us updated successfully');
        
    }
}
