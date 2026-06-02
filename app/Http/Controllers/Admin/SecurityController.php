<?php

namespace App\Http\Controllers\admin;

use App\Models\AboutUs;
use Illuminate\Http\Request;
use App\Models\PrivacyPolicy;
use App\Models\TermCondition;
use App\Models\UserRolePermission;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Validator;


class SecurityController extends Controller
{
    public function PrivacyPolicy()
    {
        $data = PrivacyPolicy::first();
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


        return view('admin.privacyPolicy.index', compact('data',  'sideMenuPermissions'));
    }


    public function PrivacyPolicyView()
    {
        $data = PrivacyPolicy::first();
        return view('admin.privacyPolicy.privacypolicy', compact('data'));
    }

    public function PrivacyPolicyEdit()
    {
        $data = PrivacyPolicy::first();
        
        return view('admin.privacyPolicy.edit', compact('data'));
    }
    public function PrivacyPolicyUpdate(Request $request)
    {
  $validator = Validator::make($request->all(), [
        'description' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }




        $data = PrivacyPolicy::first();
        // PrivacyPolicy::find($data->id)->update($request->all());
        if ($data) {
            $data->update($request->all());
        } else {
            PrivacyPolicy::create($request->all());
        }
        return redirect('/admin/privacy-policy')->with('success', 'Privacy & Policy updated successfully');
    }
    public function TermCondition()
    {
        $data = TermCondition::first();

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

        
        
        
        return view('admin.termCondition.index', compact('data', 'sideMenuPermissions'));
    }

    public function TermConditionView()
    {
        $data = TermCondition::first();
        return view('admin.termCondition.termcondition', compact('data'));
    }

    public function TermConditionEdit()
    {
        $data = TermCondition::first();
        return view('admin.termCondition.edit', compact('data'));
    }
    public function TermConditionUpdate(Request $request)
    {
        $request->validate([
            'description' => 'required'
        ]);
        // dd($request);
        $data = TermCondition::first();
        // TermCondition::find($data->id)->update($request->all());
        if ($data) {
            $data->update($request->all());
        } else {
            TermCondition::create($request->all());
        }
        return redirect('/admin/term-condition')->with('success', 'Terms & Conditions updated successfully');
    }

    public function AboutUs()
    {
        $data = AboutUs::first();
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


        return view('admin.aboutUs.index', compact('data', 'sideMenuPermissions'));
    }


    public function AboutUsView()
    {
        $data = AboutUs::first();
        return view('admin.aboutUs.aboutus', compact('data'));
    }
    public function AboutUsEdit()
    {
        $data = AboutUs::first();
        return view('admin.aboutUs.edit', compact('data'));
    }
    public function AboutUsUpdate(Request $request)
    {
        $request->validate([
            'description' => 'required'
        ]);
        

        $data = AboutUs::first();
        // return $data;
        // AboutUs::find($data->id)->update($request->all());
        if ($data) {
            $data->update($request->all());
        } else {
            AboutUs::create($request->all());
        }
        return redirect('/admin/about-us')->with('success', 'About-Us updated successfully');
    }
}
