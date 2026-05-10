<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CurrencyService;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        if (! $product->is_active) {
            abort(404);
        }

        $product->load('category', 'productImages', 'productAttributes');
        $currency = app(CurrencyService::class);

        $relatedProducts = Product::query()
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('category')
            ->inRandomOrder()
            ->limit(8)
            ->get();

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
            'metaDescription' => $product->meta_description ?: ($product->short_description ?: $product->name),
            'product' => $product,
            'currency' => $currency,
            'relatedProducts' => $relatedProducts,
            'reviews' => $reviews,
            'reviewStats' => $reviewStats,
        ]);
    }
}
