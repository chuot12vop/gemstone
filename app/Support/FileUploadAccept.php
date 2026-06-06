<?php

namespace App\Support;

final class FileUploadAccept
{
    public const RASTER = '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp';

    public const WITH_SVG = self::RASTER . ',.svg,image/svg+xml';
}
