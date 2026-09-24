<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\LineItem;
use App\Services\LineItemSyncService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(LineItemSyncService $lineItemSync)
    {
        // Auto-sync + remove duplicates (normalized name match)
        $lineItemSync->dedupeExisting();
        $lineItemSync->syncFromSaleItems();
        $lineItemSync->dedupeExisting();

        $lineItems = LineItem::with('category')
            ->whereRaw("LOWER(TRIM(name)) NOT IN (?, ?, ?)", [
                    'non-tradable',
                    'jewellery',
                    'tops'
                ])
            ->orderByDesc('id')
            ->get()
            ->unique(function ($item) use ($lineItemSync) {
                $normalized = $lineItemSync->normalize($item->name);
                return $normalized !== null ? mb_strtolower($normalized) : (string) $item->id;
            })
            ->sortBy([
                // Unassigned first, then other categories together
                function ($item) {
                    if (empty($item->category_id) || empty($item->category->name ?? null)) {
                        return '0_unassigned';
                    }
                    return '1_' . mb_strtolower($item->category->name);
                },
                // Newest unassigned first; alphabetical within assigned categories
                function ($item) {
                    if (empty($item->category_id) || empty($item->category->name ?? null)) {
                        return sprintf('%012d', 999999999999 - (int) $item->id);
                    }
                    return mb_strtolower($item->name ?? '');
                },
            ])
            ->values();

        $categories = Category::all();

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
