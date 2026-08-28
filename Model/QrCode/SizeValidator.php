<?php
declare(strict_types=1);

namespace TVTCommerce\QrCodeGenerator\Model\QrCode;

/**
 * Validates/clamps the optional "size" (pixels) request parameter for QR code generation.
 *
 * Deliberately framework-free (no Magento dependency) so it is directly unit-testable — see
 * tests/Unit/Model/QrCode/SizeValidatorTest.php. Also deliberately does not throw on
 * out-of-range input: an admin fiddling with the URL query string should get a clamped, still-
 * usable QR code back, not a hard error, so this fails soft by clamping rather than rejecting.
 */
class SizeValidator
{
    /**
     * Default size (pixels) used when no "size" parameter is supplied at all.
     */
    public const DEFAULT_SIZE = 300;

    /**
     * Minimum allowed size (pixels). Below this a QR code with enough modules to encode a
     * typical storefront URL becomes unscannable.
     */
    public const MIN_SIZE = 100;

    /**
     * Maximum allowed size (pixels). Above this there is no practical benefit for a printed
     * tag/flyer/POS use case, only wasted memory generating an oversized PNG.
     */
    public const MAX_SIZE = 1000;

    /**
     * Resolve the requested size to a value guaranteed to be within [MIN_SIZE, MAX_SIZE].
     *
     * @param int|null $requestedSize Raw "size" request parameter, or null when absent.
     * @return int
     */
    public function validate(?int $requestedSize): int
    {
        if ($requestedSize === null) {
            return self::DEFAULT_SIZE;
        }

        if ($requestedSize < self::MIN_SIZE) {
            return self::MIN_SIZE;
        }

        if ($requestedSize > self::MAX_SIZE) {
            return self::MAX_SIZE;
        }

        return $requestedSize;
    }
}
