<?php
declare(strict_types=1);

namespace TVTCommerce\QrCodeGenerator\Tests\Unit\Model\QrCode;

use PHPUnit\Framework\TestCase;
use TVTCommerce\QrCodeGenerator\Model\QrCode\SizeValidator;

final class SizeValidatorTest extends TestCase
{
    private SizeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SizeValidator();
    }

    public function testNullReturnsTheDefaultSize(): void
    {
        self::assertSame(SizeValidator::DEFAULT_SIZE, $this->validator->validate(null));
    }

    public function testAnInRangeSizeIsReturnedUnchanged(): void
    {
        self::assertSame(500, $this->validator->validate(500));
    }

    public function testExactlyTheMinimumIsReturnedUnchanged(): void
    {
        self::assertSame(SizeValidator::MIN_SIZE, $this->validator->validate(SizeValidator::MIN_SIZE));
    }

    public function testExactlyTheMaximumIsReturnedUnchanged(): void
    {
        self::assertSame(SizeValidator::MAX_SIZE, $this->validator->validate(SizeValidator::MAX_SIZE));
    }

    public function testBelowTheMinimumIsClampedUpToTheMinimum(): void
    {
        self::assertSame(SizeValidator::MIN_SIZE, $this->validator->validate(1));
    }

    public function testAboveTheMaximumIsClampedDownToTheMaximum(): void
    {
        self::assertSame(SizeValidator::MAX_SIZE, $this->validator->validate(999999));
    }

    public function testZeroIsClampedUpToTheMinimum(): void
    {
        self::assertSame(SizeValidator::MIN_SIZE, $this->validator->validate(0));
    }

    public function testANegativeSizeIsClampedUpToTheMinimum(): void
    {
        self::assertSame(SizeValidator::MIN_SIZE, $this->validator->validate(-50));
    }

    public function testOneBelowTheMinimumBoundaryIsClamped(): void
    {
        self::assertSame(
            SizeValidator::MIN_SIZE,
            $this->validator->validate(SizeValidator::MIN_SIZE - 1)
        );
    }

    public function testOneAboveTheMaximumBoundaryIsClamped(): void
    {
        self::assertSame(
            SizeValidator::MAX_SIZE,
            $this->validator->validate(SizeValidator::MAX_SIZE + 1)
        );
    }
}
