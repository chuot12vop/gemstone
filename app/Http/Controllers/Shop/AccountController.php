<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CurrencyService;
use App\Support\PhoneValidation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = Auth::user();

        return view('shop.account.index', [
            'title' => 'My account',
            'metaDescription' => 'Your profile and order history.',
            'user' => $user,
            'recentOrders' => $this->ordersForUser($user)->take(5)->get(),
        ]);
    }

    public function profile(): View
    {
        return view('shop.account.profile', [
            'title' => 'Account profile',
            'metaDescription' => 'Update your name and phone number.',
            'user' => Auth::user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:160',
            'phone' => PhoneValidation::rules(9, 30),
        ]);

        $user->update([
            'name' => $validated['name'],
            'phone' => trim((string) $validated['phone']),
        ]);

        return redirect()->route('shop.account.profile')->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $rules = [
            'password' => 'required|string|min:8|confirmed',
        ];

        if ($user->password) {
            $rules['current_password'] = 'required|current_password';
        }

        $validated = $request->validate($rules);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('shop.account.profile')->with('success', 'Password updated.');
    }

    public function orders(CurrencyService $currency): View
    {
        return view('shop.account.orders', [
            'title' => 'Order history',
            'metaDescription' => 'Your past orders.',
            'orders' => $this->ordersForUser(Auth::user())
                ->with(['items.product'])
                ->paginate(10),
            'currency' => $currency,
        ]);
    }

    public function orderShow(string $order_number, CurrencyService $currency): View
    {
        $order = $this->ordersForUser(Auth::user())
            ->where('order_number', $order_number)
            ->with(['items.product'])
            ->firstOrFail();

        return view('shop.account.order-show', [
            'title' => 'Order '.$order->order_number,
            'metaDescription' => 'Order details.',
            'order' => $order,
            'currency' => $currency,
        ]);
    }

    /**
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Builder<Order>
     */
    private function ordersForUser($user)
    {
        return Order::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('customer_email', $user->email);
            })
            ->latest();
    }
}
