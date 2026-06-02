<?php

namespace App\Http\Controllers;

use App\Models\blog;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\UserRolePermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class BlogController extends Controller
{
    //

    public function Index()
{
    $blogs = Blog::orderBy('position', 'asc')->get();

    $sideMenuPermissions = collect();

    if (!Auth::guard('admin')->check()) {
        $user = Auth::guard('subadmin')->user()->load('roles');
        $roleId = $user->role_id;
        
        $permissions = UserRolePermission::with(['permission', 'sideMenue'])
            ->where('role_id', $roleId)
            ->get();

        $sideMenuPermissions = $permissions->groupBy('sideMenue.name')->map(function ($items) {
            return $items->pluck('permission.name');
        });
    }

    return view('admin.blogs.index', compact('blogs', 'sideMenuPermissions'));
}

// Add this new method for handling toggle status
public function toggleStatus(Request $request)
{
    $blog = Blog::find($request->id);
    
    if ($blog) {
        $blog->toggle = $request->status;
        $blog->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'new_status' => $blog->toggle ? 'Activated' : 'Deactivated'
        ]);
    }
    
    return response()->json([
        'success' => false,
        'message' => 'Blog not found'
    ], 404);
}

    public function create()
    {
        return view('admin.blogs.create');
    }



public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'title' => 'required',
        'slug' => 'required|unique:blogs,slug',
        'content' => 'required',
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    // Handle image upload
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imageName = time() . '_' . Str::slug($request->title) . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('assets/admin/blogs'), $imageName);
        $image_path = 'assets/admin/blogs/' . $imageName;


    }

    // Save data
    Blog::create([
        'title'   => $request->title,
        'slug'    => $request->slug,
        'content' => $request->content,
        'image'   =>  $image_path ?? null,
    ]);

    return redirect('/admin/blogs-index')->with('success', 'Blog created successfully');
}




   

    public function edit($id)
    {
        $data = blog::find($id);
        return view('admin.blogs.edit', compact('data'));
    }
   public function update(Request $request, $id)
{
    $find = Blog::find($id);

    if (!$find) {
        return redirect()->back()->with('error', 'Blog not found');
    }

    // Validation
    $validator = Validator::make($request->all(), [
        'title'   => 'required|string|max:255',
        'slug'    => 'required|string|max:255|unique:blogs,slug,' . $id,
        'content' => 'required|string',
        'image'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    // Handle image upload
    $image_path = $find->image; // default to existing image
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imageName = time() . '_' . Str::slug($request->title) . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('assets/admin/blogs'), $imageName);
        $image_path = 'assets/admin/blogs/' . $imageName;

        // Optional: delete old image
        if ($find->image && file_exists(public_path($find->image))) {
            unlink(public_path($find->image));
        }
    }

    // Update data
    $find->update([
        'title'   => $request->title,
        'slug'    => $request->slug,
        'content' => $request->content,
        'image'   => $image_path,
    ]);

    return redirect('/admin/blogs-index')->with('success', 'Blog updated successfully');
}


    

    public function delete($id) {
        $blog = blog::find($id);
        if ($blog) {
            $blog->delete();
            return redirect('/admin/blogs-index')->with('success', 'Blog deleted successfully');
        } else {
            return redirect('/admin/blogs-index')->with('error', 'Blog not found');
        }
    }

    // Method to reorder blogs based on position

public function reorder(Request $request)
{
    foreach ($request->order as $item) {
        blog::where('id', $item['id'])->update(['position' => $item['position']]);
    }

    return response()->json(['status' => 'success']);
}

}
