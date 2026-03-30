<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryTreeController extends Controller
{
    public function index()
    {
        $tree = Category::defaultOrder()->get()->toTree();

        return view('admin.category-tree.index', compact('tree'));
    }
}
