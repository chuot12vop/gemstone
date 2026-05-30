<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\CurrencyService;
use App\Support\ProductDetailPolicies;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        if (! $product->is_active) {
            abort(404);
        }

        $product->load([
            'category',
            'productImages',
            'productAttributes',
            'variants' => static fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
            'upsellProducts' => static fn ($q) => $q->where('is_active', true)->with([
                'variants' => static fn ($vq) => $vq->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
            ]),
        ]);
        $currency = app(CurrencyService::class);

        $relatedProducts = Product::query()
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
            ->inRandomOrder()
            ->limit(8)
            ->get();

        $bestSellerProducts = collect();
        $bestSellersCategory = Category::query()->where('slug', 'Best-Sellers')->first();
        if ($bestSellersCategory) {
            $bestSellerProducts = Product::query()
                ->where('is_active', true)
                ->where('category_id', $bestSellersCategory->id)
                ->where('id', '!=', $product->id)
                ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
                ->inRandomOrder()
                ->limit(8)
                ->get();
        }

        $reviews = $product->approvedReviews()->with('images')->get();
        $reviewStats = [
            'count' => $reviews->count(),
            'average' => $reviews->isEmpty() ? 0.0 : round($reviews->avg('rating'), 2),
            'distribution' => collect([5, 4, 3, 2, 1])
                ->mapWithKeys(fn (int $star) => [$star => $reviews->where('rating', $star)->count()])
                ->all(),
        ];

        return view('shop.product', [
            'title' => $product->meta_title ?: $product->name,
            'metaDescription' => $product->meta_description ?: (
                $product->short_description
                ?: Str::limit(strip_tags((string) $product->description), 160, '')
                ?: $product->name
            ),
            'product' => $product,
            'currency' => $currency,
            'bestSellerProducts' => $bestSellerProducts,
            'relatedProducts' => $relatedProducts,
            'productPolicies' => ProductDetailPolicies::rows(),
            'reviews' => $reviews,
            'reviewStats' => $reviewStats,
        ]);
    }
}
