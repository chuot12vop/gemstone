<?php

namespace App\Http\Controllers\Shop\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('shop.login', [
            'title' => 'Sign in',
            'metaDescription' => 'Sign in to checkout and view your orders.',
            'checkoutRequired' => $this->checkoutLoginRequired(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! $user->password) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records, or this account uses Google sign-in.'])
                ->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()
            ->intended(route('shop.account.index'))
            ->with('success', 'Signed in successfully.');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('shop.home');
    }

    private function checkoutLoginRequired(): bool
    {
        $intended = session('url.intended');

        return is_string($intended) && str_contains($intended, '/checkout');
    }
}
