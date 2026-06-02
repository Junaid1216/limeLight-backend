<?php

namespace App\Http\Controllers\Admin;

use App\Models\Seo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SeoController extends Controller
{
   
      public function index()
    {
        $pages = Seo::all(); 
        $currentPage = Seo::first(); 

        return view('admin.seo.index', compact('pages', 'currentPage'));
    }
   
    public function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'pages' => 'required|array',
            'pages.*.page' => 'required|string|max:255',
            'pages.*.title' => 'nullable|string|max:255',
            'pages.*.description' => 'nullable|string|max:500',
            'pages.*.og_title' => 'nullable|string|max:255',
            'pages.*.og_description' => 'nullable|string|max:500',
            'pages.*.keywords' => 'nullable|string|max:255',
        ]);

        try {
            foreach ($validated['pages'] as $pageData) {
                Seo::updateOrCreate(
                    ['page' => $pageData['page']],
                    $pageData
                );
            }
            
            return response()->json([
                'success' => true,
                'message' => 'SEO data updated successfully',
                'data' => $validated['pages']
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update SEO data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        $pages = Seo::all();
        $currentPage = Seo::findOrFail($id);

        return view('admin.seo.index', compact('pages', 'currentPage'));
    }

    public function update(Request $request, $id)
    {
        $seo = Seo::findOrFail($id);
        $seo->update($request->all());

        return redirect()->back()->with('success', 'SEO updated successfully');
    }

    public function getPage($id)
{
    $page = Page::findOrFail($id);

    return response()->json([
        'html' => view('admin.seo.partials.tab', compact('page'))->render()
    ]);
}

}