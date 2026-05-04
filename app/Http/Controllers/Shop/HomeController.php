<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        return view('shop.home', [
            'title' => 'Gemstone Jewelry & Feng Shui — Taichi-inspired wellness',
            'metaDescription' => 'Premium gemstone jewelry for balance, luck, and intention. Ethically sourced, handcrafted for the US market.',
            'featured' => Product::query()->where('is_active', true)->with('category')->latest()->take(6)->get(),
            'categories' => Category::query()->orderBy('sort_order')->take(3)->get(),
            'products' => Product::query()->where('is_active', true)->with('category')->latest()->take(6)->get(),
            'currency' => floatval(20000.00),
        ]);
    }
}
