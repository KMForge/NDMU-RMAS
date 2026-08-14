<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use RuntimeException;

class OfficialFormQrCodeGenerator
{
    /**
     * Generate an inline SVG or Data URI string for the given URL.
     */
    public function generateSvgDataUri(string $url): string
    {
        if (! class_exists(QRCode::class)) {
            throw new RuntimeException('chillerlan/php-qrcode package is required for QR code generation.');
        }

        $options = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_M,
            'addQuietzone' => true,
            'outputBase64' => true,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($url);
    }

    /**
     * Generate raw SVG markup string for embedding directly in Blade.
     */
    public function generateRawSvg(string $url): string
    {
        if (! class_exists(QRCode::class)) {
            throw new RuntimeException('chillerlan/php-qrcode package is required for QR code generation.');
        }

        $options = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_M,
            'addQuietzone' => true,
            'outputBase64' => false,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($url);
    }
}
