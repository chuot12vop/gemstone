<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'title' => 'Dashboard',
            'breadcrumbs' => [
                ['label' => 'Dashboard'],
            ],
            'productCount' => Product::query()->count(),
            'orderCount' => Order::query()->count(),
            'recentOrders' => Order::query()->latest()->take(8)->get(),
        ]);
    }
}
