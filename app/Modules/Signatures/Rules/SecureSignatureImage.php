<?php

namespace App\Modules\Signatures\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class SecureSignatureImage implements ValidationRule
{
    private const ALLOWED_MIME_TYPES = ['image/png', 'image/jpeg'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('The signature image could not be uploaded.');

            return;
        }

        $path = $value->getRealPath();

        if (! is_string($path) || ! is_file($path) || filesize($path) === 0) {
            $fail('The signature image must not be empty.');

            return;
        }

        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $header = file_get_contents($path, false, null, 0, 12);
        $isPng = is_string($header) && str_starts_with($header, "\x89PNG\r\n\x1a\n");
        $isJpeg = is_string($header) && str_starts_with($header, "\xff\xd8\xff");

        if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true) || (! $isPng && ! $isJpeg)) {
            $fail('The signature must be a genuine PNG or JPEG image.');

            return;
        }

        $dimensions = @getimagesize($path);

        if ($dimensions === false) {
            $fail('The signature image is corrupted or unreadable.');

            return;
        }

        [$width, $height] = $dimensions;

        if ($width < 100 || $height < 40 || $width > 3000 || $height > 3000 || ($width * $height) > 9_000_000) {
            $fail('The signature image dimensions must be between 100×40 and 3000×3000 pixels.');
        }
    }
}
