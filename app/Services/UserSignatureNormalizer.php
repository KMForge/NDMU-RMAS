<?php

namespace App\Services;

use InvalidArgumentException;

class UserSignatureNormalizer
{
    /**
     * Normalize an uploaded image binary into clean PNG bytes.
     */
    public function normalize(string $binaryData): string
    {
        if (! extension_loaded('gd') || ! function_exists('imagecreatefromstring')) {
            // Fallback for environments without GD extension (e.g. CLI php without ext-gd)
            if ($binaryData === '' || ! str_starts_with($binaryData, "\x89PNG\r\n\x1a\n")) {
                throw new InvalidArgumentException('Signature image file must be a valid PNG image.');
            }
            if (strlen($binaryData) > 2 * 1024 * 1024) {
                throw new InvalidArgumentException('Signature image file exceeds maximum allowed size.');
            }

            return $binaryData;
        }

        if ($binaryData === '') {
            throw new InvalidArgumentException('Signature image file is empty.');
        }

        $imageInfo = @getimagesizefromstring($binaryData);
        if ($imageInfo === false) {
            throw new InvalidArgumentException('Signature image file is invalid or corrupted.');
        }

        [$srcWidth, $srcHeight, $type] = $imageInfo;

        if (! in_array($type, [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            throw new InvalidArgumentException('Only PNG and JPEG images are supported.');
        }

        if ($srcWidth > 4000 || $srcHeight > 2000 || $srcWidth < 10 || $srcHeight < 10) {
            throw new InvalidArgumentException('Signature image dimensions must be between 10x10 and 4000x2000 pixels.');
        }

        $source = @imagecreatefromstring($binaryData);
        if ($source === false) {
            throw new InvalidArgumentException('Failed to decode signature image.');
        }

        $maxWidth = (int) config('signatures.normalized_dimensions.max_width', 1200);
        $maxHeight = (int) config('signatures.normalized_dimensions.max_height', 400);

        $ratio = min($maxWidth / $srcWidth, $maxHeight / $srcHeight, 1.0);
        $dstWidth = (int) max(1, round($srcWidth * $ratio));
        $dstHeight = (int) max(1, round($srcHeight * $ratio));

        $target = imagecreatetruecolor($dstWidth, $dstHeight);
        if ($target === false) {
            imagedestroy($source);
            throw new InvalidArgumentException('Failed to process image buffer.');
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);

        if ($type === IMAGETYPE_JPEG) {
            $white = imagecolorallocate($target, 255, 255, 255);
            imagefill($target, 0, 0, $white);
            imagealphablending($target, true);
        } else {
            $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
            imagefill($target, 0, 0, $transparent);
            imagealphablending($target, true);
        }

        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $dstWidth,
            $dstHeight,
            $srcWidth,
            $srcHeight,
        );

        ob_start();
        imagepng($target, null, 9);
        $pngData = ob_get_clean();

        imagedestroy($source);
        imagedestroy($target);

        if (! is_string($pngData) || $pngData === '') {
            throw new InvalidArgumentException('Failed to encode normalized PNG signature.');
        }

        return $pngData;
    }
}
