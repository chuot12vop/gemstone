<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Setting;
use App\Services\CurrencyService;
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
        View::composer('layouts.shop', function ($view) {
            $defaults = [
                'site_name' => config('app.name'),
                'site_logo' => '',
                'security_policy' => '',
                'privacy_policy' => '',
                'return_policy' => '',
                'terms_of_service' => '',
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

            $catalogNavCategories = Category::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->with(['products' => function ($q) {
                    $q->where('is_active', true)
                        ->orderBy('name')
                        ->limit(10)
                        ->select('id', 'category_id', 'name', 'slug');
                }])
                ->get(['id', 'name', 'slug', 'sort_order']);

            $view->with('currency', app(CurrencyService::class))
                ->with('siteSettings', $defaults)
                ->with('shopFront', ShopFrontSettings::resolve())
                ->with('catalogNavCategories', $catalogNavCategories);
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
