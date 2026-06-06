<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Support\MarketingSubscribers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WelcomeOfferController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:190',
        ]);

        MarketingSubscribers::subscribe($validated['email']);

        return response()->json(['ok' => true]);
    }
}
