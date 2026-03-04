<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\Tests;

use Hrabo\AresBundle\Normalizer\IcoNormalizer;
use PHPUnit\Framework\TestCase;

final class IcoNormalizerTest extends TestCase
{
    public function testNormalizePadsLeftToEightDigits(): void {
        self::assertSame('00006947', IcoNormalizer::normalize('6947'));
    }

    public function testNormalizeTrimsWhitespace(): void {
        self::assertSame('00006947', IcoNormalizer::normalize("  6947 \n"));
    }

    public function testNormalizeKeepsEightDigitInput(): void {
        self::assertSame('12345678', IcoNormalizer::normalize('12345678'));
    }

    public function testNormalizeRejectsEmptyString(): void {
        $this->expectException(\InvalidArgumentException::class);
        IcoNormalizer::normalize('');
    }

    public function testNormalizeRejectsTooLongValue(): void {
        $this->expectException(\InvalidArgumentException::class);
        IcoNormalizer::normalize('123456789');
    }

    public function testNormalizeRejectsNonDigits(): void {
        $this->expectException(\InvalidArgumentException::class);
        IcoNormalizer::normalize('12a');
    }
}
