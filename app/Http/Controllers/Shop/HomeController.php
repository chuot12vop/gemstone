<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use App\Services\CurrencyService;
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

        $productQuery = Product::query()->where('is_active', true)->with('category')->latest();
        $spotlightProducts = (clone $productQuery)->take(3)->get();
        $featured = (clone $productQuery)->skip(3)->take(6)->get();

        return view('shop.home', [
            'siteSettings' => $defaults,
            'title' => 'Gemstone Jewelry & Feng Shui — Taichi-inspired wellness',
            'metaDescription' => 'Premium gemstone jewelry for balance, luck, and intention. Ethically sourced, handcrafted for the US market.',
            'spotlightProducts' => $spotlightProducts,
            'featured' => $featured,
            'currency' => app(CurrencyService::class),
        ]);
    }
}
