<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Support\ContactFormSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:160',
            'phone' => 'required|string|max:40',
            'email' => 'nullable|email|max:190',
            'address' => 'required|string|max:500',
            'product' => 'nullable|string|max:190',
            'message' => 'nullable|string|max:2000',
        ]);

        try {
            $this->syncToGoogleSheet($validated);
        } catch (\RuntimeException $e) {
            if ($this->wantsContactJson($request)) {
                return response()->json(['message' => $e->getMessage()], 502);
            }

            return redirect()
                ->route('shop.contact')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        Contact::query()->create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'],
            'product' => $validated['product'] ?? null,
            'message' => $validated['message'] ?? null,
            'status' => Contact::STATUS_NEW,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        $message = "Thanks {$validated['name']}, our team will reach out shortly.";

        if ($this->wantsContactJson($request)) {
            return response()->json([
                'ok' => true,
                'message' => $message,
            ]);
        }

        return redirect()
            ->route('shop.contact')
            ->with('success', $message);
    }

    /**
     * @param  array{name: string, phone: string, email?: string|null, address: string, product?: string|null, message?: string|null}  $data
     */
    private function syncToGoogleSheet(array $data): void
    {
        $url = ContactFormSettings::googleScriptUrl();
        if ($url === '') {
            return;
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? '',
                    'address' => $data['address'],
                    'product' => $data['product'] ?? '',
                    'message' => $data['message'] ?? '',
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Google Sheet contact sync failed', ['error' => $e->getMessage()]);

            throw new \RuntimeException('Could not reach Google Sheets. Please try again in a moment.');
        }

        if (! $response->successful()) {
            Log::warning('Google Sheet contact sync failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Could not save your message to Google Sheets. Please try again.');
        }
    }

    private function wantsContactJson(Request $request): bool
    {
        return $request->header('X-Contact-Form') === '1'
            || $request->ajax()
            || $request->expectsJson();
    }
}
