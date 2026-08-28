<?php
declare(strict_types=1);

namespace TVTCommerce\QrCodeGenerator\Model\QrCode;

/**
 * Adapter contract: given a URL string and a target size, return raw PNG bytes.
 *
 * Kept as a one-method interface deliberately, so the QR-generation mechanism (see
 * EndroidPngGenerator, and this module's README for exactly which library/version that is and
 * why) is fully swappable/mockable behind this single seam — nothing else in this module talks
 * to the underlying QR library directly.
 */
interface PngGeneratorInterface
{
    /**
     * Generate a PNG QR code encoding $data.
     *
     * @param string $data        The payload to encode (a full storefront URL in this module's
     *                             usage).
     * @param int    $sizePixels  Target width/height of the square PNG, in pixels.
     * @return string             Raw PNG file bytes.
     */
    public function generate(string $data, int $sizePixels): string;
}
