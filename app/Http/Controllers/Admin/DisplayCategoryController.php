<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DisplayCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DisplayCategoryController extends Controller
{
    public function index()
    {
        $categories = DisplayCategory::orderBy('name')->get();

        return view('admin.displaycategory.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'nullable|boolean',
        ]);

        $slug = Str::slug($request->name);
        if ($slug === '') {
            return back()->with('error', 'Invalid category name')->withInput();
        }

        if (DisplayCategory::where('slug', $slug)->exists()) {
            return back()->with('error', 'Category already exists')->withInput();
        }

        DisplayCategory::create([
            'name' => $request->name,
            'slug' => $slug,
            'status' => $request->has('status') ? (bool) $request->status : true,
        ]);

        return redirect()->route('display.category.index')->with('success', 'Display Category Added Successfully');
    }

    public function update(Request $request, $id)
    {
        $category = DisplayCategory::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'nullable|boolean',
        ]);

        $slug = Str::slug($request->name);
        if ($slug === '') {
            return back()->with('error', 'Invalid category name')->withInput();
        }

        if (DisplayCategory::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
            return back()->with('error', 'Category already exists')->withInput();
        }

        $category->update([
            'name' => $request->name,
            'slug' => $slug,
            'status' => $request->has('status') ? (bool) $request->status : $category->status,
        ]);

        return redirect()->route('display.category.index')->with('success', 'Display Category Updated Successfully');
    }

    public function delete($id)
    {
        $category = DisplayCategory::findOrFail($id);
        $category->delete();

        return redirect()->route('display.category.index')->with('success', 'Display Category Deleted Successfully');
    }
}
