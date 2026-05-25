<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ImageWebpEncoder
{
    private const QUALITY = 82;

    public function canEncode(): bool
    {
        return extension_loaded('gd') && function_exists('imagewebp');
    }

    /**
     * Encode an uploaded image as WebP at $destinationPath (absolute filesystem path).
     */
    public function encodeUploadedFile(UploadedFile $file, string $destinationPath): bool
    {
        if (! $this->canEncode()) {
            return false;
        }

        $source = $file->getRealPath();
        if ($source === false || ! is_readable($source)) {
            return false;
        }

        $image = $this->createImageResource($source, $file->getMimeType());
        if ($image === null) {
            return false;
        }

        $this->prepareAlpha($image);

        $dir = dirname($destinationPath);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            imagedestroy($image);

            return false;
        }

        $ok = imagewebp($image, $destinationPath, self::QUALITY);
        imagedestroy($image);

        return $ok && is_file($destinationPath);
    }

    /**
     * @return \GdImage|resource|null
     */
    private function createImageResource(string $path, ?string $mime)
    {
        $mime = strtolower((string) $mime);
        if ($mime === '' && is_readable($path)) {
            $detected = mime_content_type($path);
            $mime = is_string($detected) ? strtolower($detected) : '';
        }

        $image = match (true) {
            str_contains($mime, 'webp') => @imagecreatefromwebp($path),
            str_contains($mime, 'png') => @imagecreatefrompng($path),
            str_contains($mime, 'gif') => @imagecreatefromgif($path),
            str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => @imagecreatefromjpeg($path),
            default => null,
        };

        if ($this->isGdImage($image)) {
            return $image;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $image = match ($extension) {
            'webp' => @imagecreatefromwebp($path),
            'png' => @imagecreatefrompng($path),
            'gif' => @imagecreatefromgif($path),
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            default => null,
        };

        return $this->isGdImage($image) ? $image : null;
    }

    /**
     * @param \GdImage|resource $image
     */
    private function prepareAlpha($image): void
    {
        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($image);
        }
        imagealphablending($image, true);
        imagesavealpha($image, true);
    }

    /**
     * @param mixed $value
     */
    private function isGdImage($value): bool
    {
        if ($value instanceof \GdImage) {
            return true;
        }

        return is_resource($value) && get_resource_type($value) === 'gd';
    }
}
