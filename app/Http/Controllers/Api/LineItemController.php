<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LineItem;
use Illuminate\Http\Request;

class LineItemController extends Controller
{
    public function store(Request $request)
    {
        // $request->validate([
        //     'items' => 'required|array',
        //     'items.*.id' => 'required',
        //     'items.*.name' => 'required',
        // ]);

        foreach ($request->items as $item) {

            LineItem::updateOrCreate(
                ['api_id' => $item['id']],
                [
                    'name' => $item['name'],
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Line items synced successfully'
        ]);
    }
}
