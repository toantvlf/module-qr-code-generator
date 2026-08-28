<?php
declare(strict_types=1);

namespace TVTCommerce\QrCodeGenerator\Tests\Unit\Model\QrCode;

use PHPUnit\Framework\TestCase;
use TVTCommerce\QrCodeGenerator\Model\QrCode\FilenameSanitizer;

final class FilenameSanitizerTest extends TestCase
{
    private FilenameSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new FilenameSanitizer();
    }

    public function testASimpleLowercaseNameIsPreservedInTheFilename(): void
    {
        self::assertSame('qr-code-widget.png', $this->sanitizer->toPngFilename('widget'));
    }

    public function testUppercaseIsLowercased(): void
    {
        self::assertSame('qr-code-widget.png', $this->sanitizer->toPngFilename('WIDGET'));
    }

    public function testSpacesBecomeSingleHyphens(): void
    {
        self::assertSame('qr-code-my-cool-product.png', $this->sanitizer->toPngFilename('My Cool Product'));
    }

    public function testRunsOfWhitespaceCollapseToASingleHyphen(): void
    {
        self::assertSame('qr-code-my-cool-product.png', $this->sanitizer->toPngFilename('My  Cool   Product'));
    }

    public function testPunctuationIsStripped(): void
    {
        self::assertSame(
            'qr-code-widget-9000.png',
            $this->sanitizer->toPngFilename('Widget 9000!')
        );
    }

    public function testDisallowedSymbolsCollapseToASingleHyphen(): void
    {
        // 'é' and '/' are adjacent disallowed characters, so they collapse into ONE hyphen
        // (not deleted, and not two separate hyphens) — same for the trailing '™'.
        self::assertSame(
            'qr-code-caf-mug.png',
            $this->sanitizer->toPngFilename('Café/Mug™')
        );
    }

    public function testLeadingAndTrailingHyphensAreTrimmed(): void
    {
        self::assertSame('qr-code-widget.png', $this->sanitizer->toPngFilename('  Widget!!  '));
    }

    public function testEmptyLabelFallsBackToAGenericFilename(): void
    {
        self::assertSame('qr-code-qr-code.png', $this->sanitizer->toPngFilename(''));
    }

    public function testALabelThatIsPureSymbolsFallsBackToAGenericFilename(): void
    {
        self::assertSame('qr-code-qr-code.png', $this->sanitizer->toPngFilename('!!!///???'));
    }

    public function testUnderscoresAndHyphensAreBothPreserved(): void
    {
        self::assertSame(
            'qr-code-sku_abc-123.png',
            $this->sanitizer->toPngFilename('SKU_ABC-123')
        );
    }

    public function testDigitsArePreserved(): void
    {
        self::assertSame('qr-code-product42.png', $this->sanitizer->toPngFilename('Product42'));
    }
}
