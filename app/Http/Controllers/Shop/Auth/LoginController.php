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
    public function show(Request $request): View
    {
        $redirect = $request->query('redirect');
        if (is_string($redirect) && $this->isSafeLocalRedirect($redirect)) {
            session(['url.intended' => $redirect]);
        }

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

    private function isSafeLocalRedirect(string $url): bool
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        return $appUrl !== '' && str_starts_with($url, $appUrl.'/');
    }
}
