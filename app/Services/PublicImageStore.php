<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores user-uploaded images on the `public` disk.
 * Paths are returned as "/storage/<dir>/<file>" for use in views.
 */
class PublicImageStore
{
    private const PUBLIC_PREFIX = '/storage/';

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    private ImageWebpEncoder $webpEncoder;

    public function __construct(ImageWebpEncoder $webpEncoder)
    {
        $this->webpEncoder = $webpEncoder;
    }

    /**
     * @param  bool  $asWebp  When true, re-encode as WebP (falls back to original format if GD lacks WebP).
     */
    public function store(?UploadedFile $file, string $directory, bool $asWebp = false): ?string
    {
        if ($file === null || ! $file->isValid()) {
            return null;
        }

        $relativeDirectory = trim($directory, '/');

        if ($asWebp && $this->webpEncoder->canEncode()) {
            $relativePath = $relativeDirectory.'/'.Str::uuid()->toString().'.webp';
            $absolutePath = Storage::disk('public')->path($relativePath);

            if ($this->webpEncoder->encodeUploadedFile($file, $absolutePath)) {
                return self::PUBLIC_PREFIX.$relativePath;
            }
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = 'jpg';
        }

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
    public function storeMany(mixed $files, string $directory, bool $asWebp = false): Collection
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
            ->map(fn (UploadedFile $f) => $this->store($f, $directory, $asWebp))
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
