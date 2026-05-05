<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Setting;
use App\Services\CurrencyService;
use App\Support\PublicAssetUrl;
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
            $view->with('currency', app(CurrencyService::class))
                ->with('siteSettings', $defaults);
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
