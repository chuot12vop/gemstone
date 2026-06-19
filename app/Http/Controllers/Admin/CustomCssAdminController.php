<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CustomThemeStylesheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomCssAdminController extends Controller
{
    public function index()
    {
        return view('admin.custom-css.index', [
            'title' => 'Custom CSS',
            'breadcrumbs' => [
                ['label' => 'Custom CSS'],
            ],
            'viewports' => CustomThemeStylesheet::editorData(),
            'maxBytes' => CustomThemeStylesheet::MAX_BYTES,
        ]);
    }

    public function update(Request $request, string $viewport)
    {
        $config = CustomThemeStylesheet::config($viewport);
        abort_if($config === null, 404);

        $validated = $request->validate([
            'custom_css' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (strlen((string) $value) > CustomThemeStylesheet::MAX_BYTES) {
                        $fail('The custom CSS may not be greater than 512 KB.');
                    }
                },
            ],
        ]);

        $written = Storage::disk('public')->put(
            $config['path'],
            (string) ($validated['custom_css'] ?? '')
        );

        if (! $written) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unable to write the custom CSS file. Please check storage permissions.',
                    'errors' => ['custom_css' => ['Unable to write the custom CSS file. Please check storage permissions.']],
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['custom_css' => 'Unable to write the custom CSS file. Please check storage permissions.']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $config['label'].' CSS updated.',
                'viewport' => $viewport,
            ]);
        }

        return redirect()
            ->route('admin.custom-css.index')
            ->with('success', $config['label'].' CSS updated.');
    }
}
