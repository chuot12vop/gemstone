<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with('category')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('shop.catalog', [
            'title' => 'Catalog — Gemstone jewelry',
            'metaDescription' => 'Browse healing gemstones, lucky charms, and limited collections.',
            'products' => $products,
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'currentCategory' => null,
        ]);
    }

    public function category(Request $request, Category $category)
    {
        $products = Product::query()
            ->where('is_active', true)
            ->where('category_id', $category->id)
            ->with('category')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $metaTitle = $category->meta_title ?: ($category->name.' — Gemstone');
        $metaDesc = $category->meta_description ?: (string) $category->description;

        return view('shop.catalog', [
            'title' => $metaTitle,
            'metaDescription' => $metaDesc,
            'products' => $products,
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'currentCategory' => $category,
        ]);
    }
}
