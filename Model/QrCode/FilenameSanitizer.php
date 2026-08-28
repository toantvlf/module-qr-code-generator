<?php
declare(strict_types=1);

namespace TVTCommerce\QrCodeGenerator\Model\QrCode;

/**
 * Turns an entity label (product name/SKU, category name) into a safe filename for the
 * "Download PNG" Content-Disposition header.
 *
 * Deliberately framework-free (no Magento dependency) so it is directly unit-testable — see
 * tests/Unit/Model/QrCode/FilenameSanitizerTest.php. This exists specifically because entity
 * names/SKUs are free-text admin input and must never be trusted verbatim inside an HTTP
 * response header (header injection / invalid-filename risk).
 */
final class FilenameSanitizer
{
    /**
     * Build a "qr-code-<slug>.png" filename from an arbitrary label.
     *
     * @param string $label Entity name/SKU/title to derive the filename from.
     * @return string
     */
    public function toPngFilename(string $label): string
    {
        $slug = strtolower(trim($label));

        // Replace every RUN of one-or-more characters that are not a lowercase letter, digit,
        // hyphen, or underscore with a single hyphen. Using "+" (rather than replacing char by
        // char) means adjacent disallowed characters — e.g. a run of spaces, or an accented
        // letter next to a slash — collapse into one separator instead of leaving "--".
        $slug = preg_replace('/[^a-z0-9_\-]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'qr-code';
        }

        return 'qr-code-' . $slug . '.png';
    }
}
