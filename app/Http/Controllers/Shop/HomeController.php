<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Support\PublicAssetUrl;

class HomeController extends Controller
{
    public function index()
    {
        $defaults = [
            'site_name' => config('app.name'),
            'site_logo' => '',
            'home_banner' => '',
            'security_policy' => '',
            'privacy_policy' => '',
            'retail_policy' => '',
        ];

        $storedSettings = Setting::query()
            ->whereIn('key', array_keys($defaults))
            ->pluck('value', 'key')
            ->toArray();

        foreach ($storedSettings as $key => $value) {
            if (array_key_exists($key, $defaults) && $value !== null) {
                $defaults[$key] = (string) $value;
            }
        }

        $defaults['site_logo'] = PublicAssetUrl::to($defaults['site_logo']);
        $defaults['home_banner'] = PublicAssetUrl::to($defaults['home_banner']);
        return view('shop.home', [
            'siteSettings' => $defaults,
            'title' => 'Gemstone Jewelry & Feng Shui — Taichi-inspired wellness',
            'metaDescription' => 'Premium gemstone jewelry for balance, luck, and intention. Ethically sourced, handcrafted for the US market.',
            'featured' => Product::query()->where('is_active', true)->with('category')->latest()->take(6)->get(),
            'categories' => Category::query()->orderBy('sort_order')->take(3)->get(),
            'products' => Product::query()->where('is_active', true)->with('category')->latest()->take(6)->get(),
            'currency' => floatval(20000.00),
        ]);
    }
}
