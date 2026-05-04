<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        if (! $product->is_active) {
            abort(404);
        }

        $product->load('category');

        return view('shop.product', [
            'title' => $product->meta_title ?: $product->name,
            'metaDescription' => $product->meta_description ?: ($product->short_description ?: $product->name),
            'product' => $product,
        ]);
    }
}
