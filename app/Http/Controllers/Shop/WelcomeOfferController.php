<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WelcomeOfferController extends Controller
{
    private const SETTING_KEY = 'welcome_popup_subscribers';

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:190',
        ]);

        $email = strtolower(trim($validated['email']));
        $list = $this->subscriberEmails();

        if (! in_array($email, $list, true)) {
            $list[] = $email;
            Setting::query()->updateOrCreate(
                ['key' => self::SETTING_KEY],
                ['value' => json_encode(array_values($list), JSON_UNESCAPED_UNICODE)]
            );
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @return list<string>
     */
    private function subscriberEmails(): array
    {
        $raw = Setting::query()->where('key', self::SETTING_KEY)->value('value');
        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $item) {
            $e = strtolower(trim((string) $item));
            if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                $out[] = $e;
            }
        }

        return array_values(array_unique($out));
    }
}
