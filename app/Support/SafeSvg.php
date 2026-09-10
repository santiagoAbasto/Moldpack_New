<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Uploaded SVGs are served from the site's own origin, so an SVG with scripts
 * would run as stored XSS. Plain vector icons stay allowed; anything active is
 * rejected.
 */
final class SafeSvg
{
    private const FORBIDDEN = [
        '/<\s*script\b/i',
        '/<\s*(foreignObject|iframe|embed|object|handler|listener)\b/i',
        '/\son[a-z]+\s*=/i',
        '/(javascript|vbscript)\s*:/i',
        '/<!\s*(ENTITY|DOCTYPE)/i',
        '/(href|src)\s*=\s*["\']?\s*data\s*:(?!image\/(png|jpe?g|webp|gif))/i',
        '/<\?php/i',
    ];

    public static function isSvg(UploadedFile $file): bool
    {
        return $file->getMimeType() === 'image/svg+xml' || strtolower($file->getClientOriginalExtension()) === 'svg';
    }

    public static function isSafe(UploadedFile $file): bool
    {
        $contents = @file_get_contents($file->getRealPath());
        if ($contents === false || ! preg_match('/<svg\b/i', $contents)) return false;

        foreach (self::FORBIDDEN as $pattern) {
            if (preg_match($pattern, $contents)) return false;
        }

        return true;
    }
}
