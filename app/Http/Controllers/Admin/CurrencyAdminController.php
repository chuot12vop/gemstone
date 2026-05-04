<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrencyRate;
use Illuminate\Http\Request;

class CurrencyAdminController extends Controller
{
    public function index()
    {
        return view('admin.currency.index', [
            'title' => 'Currency rates',
            'breadcrumbs' => [
                ['label' => 'Currency'],
            ],
            'rates' => CurrencyRate::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function save(Request $request)
    {
        $rates = $request->input('rate_per_usd', []);
        $active = $request->input('is_active', []);
        if (! is_array($rates)) {
            return redirect()->route('admin.currency.index');
        }

        foreach ($rates as $idStr => $val) {
            $id = (int) $idStr;
            $r = CurrencyRate::query()->find($id);
            if (! $r) {
                continue;
            }
            $rateVal = (float) str_replace(',', '', (string) $val);
            if ($rateVal <= 0) {
                continue;
            }
            $r->rate_per_usd = $rateVal;
            $r->is_active = isset($active[$idStr]);
            $r->save();
        }

        return redirect()->route('admin.currency.index')->with('success', 'Rates updated.');
    }
}
