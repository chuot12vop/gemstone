<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AboutPageSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AboutAdminController extends Controller
{
    public function index(): View
    {
        $about = AboutPageSettings::resolve();

        return view('admin.about.index', [
            'title' => 'About us page',
            'breadcrumbs' => [
                ['label' => 'About us'],
            ],
            'about' => $about,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'page_title' => 'nullable|string|max:200',
            'page_summary' => 'nullable|string|max:500',
            'page_body' => 'nullable|string',
            'home_lede' => 'nullable|string|max:2000',
            'home_button_label' => 'nullable|string|max:120',
            'panels' => 'nullable|array',
            'panels.*.title' => 'nullable|string|max:200',
            'panels.*.body' => 'nullable|string',
        ]);

        AboutPageSettings::store([
            'page_title' => $validated['page_title'] ?? '',
            'page_summary' => $validated['page_summary'] ?? '',
            'page_body' => $validated['page_body'] ?? '',
            'home_lede' => $validated['home_lede'] ?? '',
            'home_button_label' => $validated['home_button_label'] ?? '',
            'panels' => $validated['panels'] ?? [],
        ]);

        return redirect()
            ->route('admin.about.index')
            ->with('success', 'About us content updated.');
    }
}
