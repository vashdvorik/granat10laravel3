<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    public function index()
    {
        return view('favorites');
    }

    public function getProducts(Request $request)
    {
        $idsRaw = $request->input('ids', '');
        $ids = array_filter(array_map('intval', explode(',', $idsRaw)));

        if (empty($ids)) {
            return response('');
        }

        // Limit to 200 IDs to prevent abuse
        $ids = array_slice($ids, 0, 200);

        $products = Product::visible()
            ->whereIn('id', $ids)
            ->with(['images', 'seller'])
            ->get();

        $html = '';
        foreach ($products as $product) {
            $html .= view('components.productcategories', compact('product'))->render();
        }

        return response($html);
    }
}
