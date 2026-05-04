<?php

namespace App\Providers;

use App\Models\Order;
use App\Services\CurrencyService;
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
            $view->with('currency', app(CurrencyService::class));
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
