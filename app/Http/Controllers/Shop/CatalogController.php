<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
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

        $sort = (string) $request->query('sort', 'related');
        if (! in_array($sort, ['newest', 'related', 'price_desc', 'price_asc'], true)) {
            $sort = 'related';
        }

        $brandCategoryIds = Product::query()
            ->where('is_active', true)
            ->whereNotNull('brand_id')
            ->select(['brand_id', 'category_id'])
            ->distinct()
            ->get()
            ->groupBy('brand_id')
            ->map(fn ($products) => $products->pluck('category_id')->map(fn ($id) => (int) $id)->values()->all());

        $brands = Brand::query()
            ->whereHas('products', fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $brandSlug = trim((string) $request->query('brand', ''));
        $selectedBrand = $brandSlug !== '' ? $brands->firstWhere('slug', $brandSlug) : null;
        if (
            $selectedBrand !== null
            && $selectedCategoryId !== null
            && ! in_array($selectedCategoryId, $brandCategoryIds->get($selectedBrand->id, []), true)
        ) {
            $selectedBrand = null;
        }

        $searchQuery = trim((string) $request->query('q', ''));

        $productsQuery = Product::query()
            ->where('is_active', true)
            ->with(['category', 'brand', 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')->with('hoverImages'), 'upsellProducts' => fn ($q) => $q->where('is_active', true)->with(['variants' => fn ($vq) => $vq->where('is_active', true)->orderBy('sort_order')->orderBy('id')->with('hoverImages')])]);

        if ($selectedBrand !== null) {
            $productsQuery->where('brand_id', $selectedBrand->id);
        }

        if ($selectedCategoryId !== null) {
            $productsQuery->where('category_id', $selectedCategoryId);
        }

        if ($searchQuery !== '') {
            $like = '%'.$searchQuery.'%';
            $productsQuery->where(function ($query) use ($like) {
                $query->where('products.name', 'like', $like)
                    ->orWhere('products.short_description', 'like', $like)
                    ->orWhere('products.description', 'like', $like);
            });
        }

        match ($sort) {
            'newest' => $productsQuery->orderByDesc('created_at')->orderByDesc('id'),
            'price_desc' => $productsQuery->orderByDesc('price_usd')->orderBy('name'),
            'price_asc' => $productsQuery->orderBy('price_usd')->orderBy('name'),
            default => $productsQuery
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->orderBy('categories.sort_order')
                ->orderBy('products.name')
                ->select('products.*'),
        };

        $products = $productsQuery->paginate(12)->withQueryString();
        $currency = app(CurrencyService::class);
        $currentCategory = $selectedCategoryId !== null ? $categories->firstWhere('id', $selectedCategoryId) : null;
        $metaTitle = $searchQuery !== ''
            ? ('Search: '.$searchQuery.' — Gemstone jewelry')
            : ($currentCategory
                ? ($currentCategory->meta_title ?: ($currentCategory->name.' — Gemstone'))
                : ($selectedBrand
                    ? ($selectedBrand->name.' — Gemstone jewelry')
                    : 'Products — Gemstone jewelry'));
        $metaDesc = $searchQuery !== ''
            ? ('Results for "'.$searchQuery.'" in our gemstone catalog.')
            : ($currentCategory
                ? ($currentCategory->meta_description ?: (string) $currentCategory->description)
                : ($selectedBrand
                    ? ('Shop '.$selectedBrand->name.' pieces and more.')
                    : 'Browse healing gemstones, lucky charms, and limited collections.'));

        return view('shop.catalog', [
            'title' => $metaTitle,
            'metaDescription' => $metaDesc,
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'brandCategoryIds' => $brandCategoryIds,
            'currentCategory' => $currentCategory,
            'currentBrand' => $selectedBrand,
            'currency' => $currency,
            'filters' => [
                'category_id' => $selectedCategoryId,
                'brand_slug' => $selectedBrand?->slug,
                'sort' => $sort,
                'q' => $searchQuery !== '' ? $searchQuery : null,
            ],
        ]);
    }
}
