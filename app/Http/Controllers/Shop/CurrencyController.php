<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function set(Request $request, CurrencyService $currency)
    {
        $request->validate(['currency' => 'required|string|max:10']);
        $currency->setCode(strtoupper($request->currency));

        return back();
    }
}
