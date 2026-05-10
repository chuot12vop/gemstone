<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Tiny helper around the `public` disk for storing user-uploaded images.
 *
 * Unifies the upload / delete logic so it doesn't have to be reimplemented in
 * every controller (products, reviews, ...). All paths are returned as
 * "/storage/<dir>/<file>" so they can be used directly in <img src="">.
 */
class PublicImageStore
{
    private const PUBLIC_PREFIX = '/storage/';

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function store(?UploadedFile $file, string $directory): ?string
    {
        if ($file === null || ! $file->isValid()) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = 'jpg';
        }

        $relativeDirectory = trim($directory, '/');
        $fileName = Str::uuid()->toString().'.'.$extension;
        $stored = $file->storeAs($relativeDirectory, $fileName, 'public');

        if (! is_string($stored) || $stored === '') {
            return null;
        }

        return self::PUBLIC_PREFIX.$stored;
    }

    /**
     * @param  array<int, mixed>|null  $files
     * @return Collection<int, string>
     */
    public function storeMany(mixed $files, string $directory): Collection
    {
        if ($files === null) {
            return collect();
        }
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }
        if (! is_array($files)) {
            return collect();
        }

        return collect($files)
            ->filter(static fn ($f) => $f instanceof UploadedFile)
            ->map(fn (UploadedFile $f) => $this->store($f, $directory))
            ->filter(static fn (?string $p) => $p !== null)
            ->values();
    }

    public function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }
        $relative = Str::startsWith($path, self::PUBLIC_PREFIX)
            ? Str::after($path, self::PUBLIC_PREFIX)
            : ltrim($path, '/');
        if ($relative !== '') {
            Storage::disk('public')->delete($relative);
        }
    }
}
