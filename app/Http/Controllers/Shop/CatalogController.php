<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\CurrencyService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        return $this->renderProductList($request, null);
    }

    public function products(Request $request)
    {
        return $this->renderProductList($request, null);
    }

    public function category(Request $request, Category $category)
    {
        return $this->renderProductList($request, $category);
    }

    private function renderProductList(Request $request, ?Category $category)
    {
        $categories = Category::query()->orderBy('sort_order')->get();

        $selectedCategoryId = $category?->id;
        if ($selectedCategoryId === null) {
            $selectedCategoryId = $request->filled('category_id') ? (int) $request->query('category_id') : null;
            if ($selectedCategoryId !== null && ! $categories->contains('id', $selectedCategoryId)) {
                $selectedCategoryId = null;
            }
        }

        $minPrice = $request->filled('min_price') ? max(0.0, (float) $request->query('min_price')) : null;
        $maxPrice = $request->filled('max_price') ? max(0.0, (float) $request->query('max_price')) : null;
        if ($minPrice !== null && $maxPrice !== null && $maxPrice < $minPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        $productsQuery = Product::query()
            ->where('is_active', true)
            ->with('category')
            ->orderBy('name');

        if ($selectedCategoryId !== null) {
            $productsQuery->where('category_id', $selectedCategoryId);
        }
        if ($minPrice !== null) {
            $productsQuery->where('price_usd', '>=', $minPrice);
        }
        if ($maxPrice !== null) {
            $productsQuery->where('price_usd', '<=', $maxPrice);
        }

        $products = $productsQuery->paginate(12)->withQueryString();
        $currency = app(CurrencyService::class);
        $currentCategory = $selectedCategoryId !== null ? $categories->firstWhere('id', $selectedCategoryId) : null;
        $metaTitle = $currentCategory
            ? ($currentCategory->meta_title ?: ($currentCategory->name.' — Gemstone'))
            : 'Products — Gemstone jewelry';
        $metaDesc = $currentCategory
            ? ($currentCategory->meta_description ?: (string) $currentCategory->description)
            : 'Browse healing gemstones, lucky charms, and limited collections.';

        return view('shop.catalog', [
            'title' => $metaTitle,
            'metaDescription' => $metaDesc,
            'products' => $products,
            'categories' => $categories,
            'currentCategory' => $currentCategory,
            'currency' => $currency,
            'filters' => [
                'category_id' => $selectedCategoryId,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
            ],
        ]);
    }
}
