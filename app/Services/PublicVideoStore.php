<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PublicVideoStore
{
    private const PUBLIC_PREFIX = '/storage/';

    public function store(?UploadedFile $file, string $directory = 'products/videos'): ?string
    {
        if ($file === null || ! $file->isValid()) {
            return null;
        }

        $relative = trim($directory, '/').'/'.Str::uuid()->toString().'.mp4';
        $stored = $file->storeAs(dirname($relative), basename($relative), 'public');

        return is_string($stored) && $stored !== '' ? self::PUBLIC_PREFIX.$stored : null;
    }

    public function delete(?string $path): void
    {
        if (! is_string($path) || trim($path) === '') {
            return;
        }

        $relative = Str::startsWith($path, self::PUBLIC_PREFIX)
            ? Str::after($path, self::PUBLIC_PREFIX)
            : ltrim($path, '/');

        if ($relative !== '' && Str::startsWith($relative, 'products/videos/')) {
            Storage::disk('public')->delete($relative);
        }
    }
}
