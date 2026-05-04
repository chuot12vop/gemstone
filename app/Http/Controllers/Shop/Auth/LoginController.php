<?php

namespace App\Http\Controllers\Shop\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        return view('shop.login', [
            'title' => 'Sign in',
            'metaDescription' => 'Sign in with your Google account.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('shop.home');
    }
}
