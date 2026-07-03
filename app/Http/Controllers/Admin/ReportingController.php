<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candela;
use Illuminate\Http\Request;

class ReportingController extends Controller
{
    public function index()
    {
        $reports = Candela::get();
        return view('admin.reporting.index', compact('reports'));
    }
}
