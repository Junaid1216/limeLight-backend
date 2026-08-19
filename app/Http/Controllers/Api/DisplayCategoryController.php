<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\DisplayCategory;

class DisplayCategoryController extends Controller
{
    /**
     * App chips for Display training tab.
     * GET /api/display-categories
     */
    public function index()
    {
        $categories = DisplayCategory::active()
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(function (DisplayCategory $category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ];
            })
            ->values();

        return ResponseHelper::success($categories, 'Display categories retrieved successfully', '200', 200);
    }
}
