<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::latest()->get();
        $categories = Announcement::categories();
        $roleOptions = Announcement::roleOptions();

        return view('admin.announcement.index', compact('announcements', 'categories', 'roleOptions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|in:hr,performance,promotions',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'roles' => 'required|array|min:1',
            'roles.*' => 'in:asm,branch_manager,sales_staff',
        ]);

        Announcement::create([
            'category' => $request->category,
            'title' => $request->title,
            'description' => $request->description,
            'roles' => $request->roles,
            'status' => 1,
        ]);

        return redirect()->route('announcement.index')->with('success', 'Announcement Created Successfully');
    }

    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $request->validate([
            'category' => 'required|in:hr,performance,promotions',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'roles' => 'required|array|min:1',
            'roles.*' => 'in:asm,branch_manager,sales_staff',
            'status' => 'nullable|boolean',
        ]);

        $announcement->update([
            'category' => $request->category,
            'title' => $request->title,
            'description' => $request->description,
            'roles' => $request->roles,
            'status' => $request->has('status') ? (bool) $request->status : $announcement->status,
        ]);

        return redirect()->route('announcement.index')->with('success', 'Announcement Updated Successfully');
    }

    public function delete($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return redirect()->route('announcement.index')->with('success', 'Announcement Deleted Successfully');
    }
}
