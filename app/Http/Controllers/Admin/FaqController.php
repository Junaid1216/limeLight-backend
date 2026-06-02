<?php

namespace App\Http\Controllers\Admin;

use App\Models\Faq;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\UserRolePermission;



class FaqController extends Controller
{
    //
    public function Faq()
    {
        $faqs = Faq::orderBy('position', 'asc')->get();

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

        

        return view('admin.faq.index', compact('faqs' , 'sideMenuPermissions'));
    }


    public function Faqscreateview()
    {
        return view('admin.faq.create');
    }


    public function Faqsstore(Request $request)
    {
	
        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'question' => 'required',
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
        Faq::create([
            'question' => $request->question,
            'description' => $request->description,
        ]);
        return redirect('/admin/faq')->with('success', 'FAQ created successfully');
    }



    public function FaqView($id)
    {
        $data = Faq::find($id);
        return view('admin.faq.faq', compact('data'));
    }


    public function FaqsEdit($id)
    {
        $data = Faq::find($id);
        return view('admin.faq.edit', compact('data'));
    }
    public function FaqsUpdate(Request $request, $id)
    {
        $data = Faq::find($id);
        // AboutUs::find($data->id)->update($request->all());
        if (!$data) {
            return ('data not found.');
        } else {
            $data->update([
            'question' => $request->question,
            'description' => $request->description,
        ]);
        }
        return redirect('/admin/faq')->with('success', 'FAQ updated successfully');
    }
    

    public function faqdelete($id) {
        $faq = Faq::find($id);
        if ($faq) {
            Faq::destroy($id);
            return redirect()->back()->with('success', 'FAQ deleted successfully');
        } else {
            return redirect()->back()->with('error', 'FAQ not found');
        }
    }


    public function reorder(Request $request)
{
    foreach ($request->order as $item) {
        Faq::where('id', $item['id'])->update(['position' => $item['position']]);
    }

    return response()->json(['status' => 'success']);
}

}
