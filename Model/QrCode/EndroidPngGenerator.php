<?php
declare(strict_types=1);

namespace TVTCommerce\QrCodeGenerator\Model\QrCode;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * PngGeneratorInterface adapter around endroid/qr-code.
 *
 * This is the exact same library, and the same QrCode/PngWriter/Color/Encoding/
 * ErrorCorrectionLevel API calls (including the exact Color() argument shape), that Magento
 * core's own Magento\TwoFactorAuth\Model\Provider\Engine\Google::getQrCodeAsPng() uses to render
 * the Google Authenticator enrollment QR code — see this module's README for the exact vendor
 * file/line reference and the dependency-availability risk that motivated requiring
 * endroid/qr-code directly in this module's own composer.json rather than only relying on
 * magento/module-two-factor-auth pulling it in.
 *
 * endroid/qr-code 6.x's QrCode/Color/Encoding are immutable (`final readonly`) value objects with
 * no fluent setters — every value is passed positionally through the QrCode constructor, matching
 * Google::getQrCodeAsPng() exactly (verified against vendor/endroid/qr-code 6.0.9 and
 * vendor/magento/module-two-factor-auth/Model/Provider/Engine/Google.php).
 *
 * Deliberately has no Magento framework dependency of its own (no constructor injection needed)
 * so the third-party call surface stays isolated to this one class.
 */
final class EndroidPngGenerator implements PngGeneratorInterface
{
    /**
     * @inheritDoc
     */
    public function generate(string $data, int $sizePixels): string
    {
        $qrCode = new QrCode(
            $data,
            new Encoding('UTF-8'),
            ErrorCorrectionLevel::High,
            $sizePixels,
            0,
            RoundBlockSizeMode::Margin,
            // Same Color(r, g, b, alpha) arguments core's Google.php passes — the trailing 0 is
            // required by endroid/qr-code's Color constructor signature, not optional.
            new Color(0, 0, 0, 0),
            new Color(255, 255, 255, 0)
        );

        $writer = new PngWriter();

        return $writer->write($qrCode)->getString();
    }
}
