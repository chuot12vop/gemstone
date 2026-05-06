<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderAdminController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = Order::query()->latest();
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('order_number', 'like', '%'.$q.'%')
                    ->orWhere('customer_email', 'like', '%'.$q.'%')
                    ->orWhere('customer_name', 'like', '%'.$q.'%');
            });
        }

        return view('admin.orders.index', [
            'title' => 'Orders',
            'breadcrumbs' => [
                ['label' => 'Orders'],
            ],
            'orders' => $query->take(100)->get(),
            'q' => $q,
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items', 'paymentTransactions']);

        return view('admin.orders.show', [
            'title' => 'Order '.$order->order_number,
            'breadcrumbs' => [
                ['label' => 'Orders', 'url' => route('admin.orders.index')],
                ['label' => $order->order_number],
            ],
            'order' => $order,
        ]);
    }

    public function status(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,shipped,cancelled',
        ]);
        $order->status = $request->status;
        $order->save();

        return redirect()->route('admin.orders.show', $order)->with('success', 'Status updated.');
    }
}
