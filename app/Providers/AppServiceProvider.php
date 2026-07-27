<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Setting;
use App\Services\CurrencyService;
use App\Support\PaymentMethodLogos;
use App\Support\PublicAssetUrl;
use App\Support\ShopFrontSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer(['layouts.shop', 'layouts.checkout'], function ($view) {
            $defaults = [
                'site_name' => config('app.name'),
                'site_logo' => '',
                'show_site_logo' => true,
                'show_site_name' => true,
                'hide_site_name_mobile' => false,
                'security_policy' => '',
                'privacy_policy' => '',
                'return_policy' => '',
                'terms_of_service' => '',
                'retail_policy' => '',
            ];

            $storedSettings = Setting::query()
                ->whereIn('key', array_merge(array_keys($defaults), ['show_site_logo', 'show_site_name', 'hide_site_name_mobile']))
                ->pluck('value', 'key')
                ->toArray();

            foreach ($storedSettings as $key => $value) {
                if (array_key_exists($key, $defaults) && $value !== null) {
                    if (in_array($key, ['show_site_logo', 'show_site_name', 'hide_site_name_mobile'], true)) {
                        $defaults[$key] = (string) $value === '1';
                    } else {
                        $defaults[$key] = (string) $value;
                    }
                }
            }

            $siteLogoPath = (string) ($storedSettings['site_logo'] ?? $defaults['site_logo']);
            $defaults['site_logo'] = PublicAssetUrl::to($siteLogoPath);

            $catalogNavCategories = Category::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->with(['products' => function ($q) {
                    $q->where('is_active', true)
                        ->orderBy('name')
                        ->select('id', 'category_id', 'name', 'slug', 'thumbnail', 'image');
                }])
                ->get(['id', 'name', 'slug', 'sort_order']);

            $featuredBrandIds = collect(json_decode((string) Setting::query()->where('key', 'home_featured_brand_ids')->value('value'), true) ?: [])
                ->map(fn ($id) => (int) $id)->filter()->unique()->values();
            $brandPositions = array_flip($featuredBrandIds->all());
            $featuredNavBrands = Brand::query()
                ->whereIn('id', $featuredBrandIds)
                ->whereHas('products', fn ($query) => $query->where('is_active', true))
                ->with(['products' => fn ($query) => $query->where('is_active', true)
                    ->orderBy('name')
                    ->select('id', 'brand_id', 'name', 'slug', 'thumbnail', 'image')])
                ->get(['id', 'name', 'slug', 'image'])
                ->sortBy(fn (Brand $brand) => $brandPositions[$brand->id] ?? PHP_INT_MAX)
                ->values();

            $view->with('currency', app(CurrencyService::class))
                ->with('siteSettings', $defaults)
                ->with('shopFront', ShopFrontSettings::resolve())
                ->with('paymentLogos', PaymentMethodLogos::all($siteLogoPath))
                ->with('catalogNavCategories', $catalogNavCategories)
                ->with('featuredNavBrands', $featuredNavBrands);
        });

        View::composer('admin.partials.topbar', function ($view) {
            if (! Auth::guard('admin')->check()) {
                $view->with('notifyCount', 0)->with('notifications', []);

                return;
            }

            $pending = Order::query()->where('status', 'pending');
            $view->with('notifyCount', (clone $pending)->count())
                ->with('notifications', $pending->latest()->take(5)->get()->map(fn ($o) => [
                    'title' => 'Pending order '.$o->order_number,
                    'body' => $o->customer_email,
                    'url' => route('admin.orders.show', $o),
                ])->all());
        });
    }
}
