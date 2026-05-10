<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:160',
            'phone' => 'required|string|max:40',
            'address' => 'required|string|max:500',
        ]);

        Contact::query()->create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'status' => Contact::STATUS_NEW,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return redirect()
            ->route('shop.contact')
            ->with('success', "Thanks {$validated['name']}, our team will reach out shortly.");
    }
}
