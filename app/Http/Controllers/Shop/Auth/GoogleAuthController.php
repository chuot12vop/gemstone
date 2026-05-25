<?php

namespace App\Http\Controllers\Shop\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            return redirect()
                ->route('login')
                ->with('error', 'Google sign-in was cancelled or failed. Try again.');
        }

        if (! $googleUser->getEmail()) {
            return redirect()
                ->route('login')
                ->with('error', 'Google did not share an email address. Allow email access and try again.');
        }

        $user = User::query()->where('google_id', $googleUser->getId())->first();

        if (! $user && $googleUser->getEmail()) {
            $user = User::query()->where('email', $googleUser->getEmail())->first();
            if ($user) {
                $user->google_id = $googleUser->getId();
                $user->avatar = $googleUser->getAvatar();
                $user->save();
            }
        }

        if (! $user) {
            $user = User::query()->create([
                'name' => $googleUser->getName() ?: ($googleUser->getNickname() ?: 'Customer'),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => null,
                'email_verified_at' => now(),
            ]);
        }

        Auth::guard('web')->login($user, true);

        if (session()->has('url.intended')) {
            return redirect()->intended(route('shop.account.index'));
        }

        if (! $user->phone) {
            return redirect()->route('shop.account.profile')
                ->with('success', 'Welcome! Please add your phone number to complete your profile.');
        }

        return redirect()->route('shop.account.index');
    }
}
