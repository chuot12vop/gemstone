<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderMailService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OrderAdminController extends Controller
{
    public function __construct(private OrderMailService $orderMail) {}

    /** @var list<string> */
    private const PAYMENT_METHODS = [
        'paypal',
        'whatsapp',
        'apple_pay',
        'venmo',
        'cashapp',
        'zelle',
    ];

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $method = trim((string) $request->get('method', ''));
        $dateFrom = $this->parseDateParam($request->get('date_from'));
        $dateTo = $this->parseDateParam($request->get('date_to'));

        if (! in_array($status, Order::STATUSES, true)) {
            $status = '';
        }
        if (! in_array($method, self::PAYMENT_METHODS, true)) {
            $method = '';
        }

        $query = Order::query()->latest();
        $this->applyOrderFilters($query, $q, $status, $method, $dateFrom, $dateTo);

        return view('admin.orders.index', [
            'title' => 'Orders',
            'breadcrumbs' => [
                ['label' => 'Orders'],
            ],
            'orders' => $query->paginate(20)->withQueryString(),
            'q' => $q,
            'status' => $status,
            'method' => $method,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'statuses' => Order::STATUSES,
            'paymentMethods' => self::PAYMENT_METHODS,
            'hasFilters' => $q !== '' || $status !== '' || $method !== '' || $dateFrom !== '' || $dateTo !== '',
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
            'status' => 'required|in:'.implode(',', Order::STATUSES),
        ]);
        $previousStatus = $order->status;
        $order->status = $request->status;
        $order->save();

        if ($previousStatus !== 'paid' && $order->status === 'paid') {
            $this->orderMail->sendPaid($order->fresh(['items']));
        }

        return redirect()->route('admin.orders.show', $order)->with('success', 'Status updated.');
    }

    private function applyOrderFilters(
        Builder $query,
        string $q,
        string $status,
        string $method,
        string $dateFrom,
        string $dateTo
    ): void {
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('order_number', 'like', '%'.$q.'%')
                    ->orWhere('customer_email', 'like', '%'.$q.'%')
                    ->orWhere('customer_name', 'like', '%'.$q.'%')
                    ->orWhere('shipping_phone', 'like', '%'.$q.'%');
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($method !== '') {
            $query->whereHas('paymentTransactions', function ($txnQuery) use ($method) {
                $txnQuery->where('payment_method', $method);
            });
        }

        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }
    }

    private function parseDateParam(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '';
        }

        try {
            return Carbon::parse(trim($value))->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }
}
