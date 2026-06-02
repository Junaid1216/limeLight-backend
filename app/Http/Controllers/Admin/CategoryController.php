<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\LineItem;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
{
    $lineItems = LineItem::with('category')->get();
    $categories = Category::all(); // unassigned

    return view('admin.category.index', compact('categories', 'lineItems'));
}

public function update(Request $request, $id)
{
    $request->validate([
        'category_id' => 'required|exists:categories,id'
    ]);

    LineItem::findOrFail($id)->update([
        'category_id' => $request->category_id
    ]);

    return back()->with('success', 'Updated Successfully');
}
}
