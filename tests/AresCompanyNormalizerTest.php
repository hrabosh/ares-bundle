<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\Tests;

use Hrabo\AresBundle\Enum\Dataset;
use Hrabo\AresBundle\Normalizer\AresCompanyNormalizer;
use PHPUnit\Framework\TestCase;

final class AresCompanyNormalizerTest extends TestCase {
    public function testNormalizeCreatesAddressTextWhenMissing(): void {
        $normalizer = new AresCompanyNormalizer();

        $company = $normalizer->normalize(Dataset::ARES, '00006947', [
            'ico' => '6947',
            'obchodniJmeno' => 'Ministerstvo financí',
            'sidlo' => [
                'nazevUlice' => 'Letenská',
                'cisloDomovni' => '525',
                'cisloOrientacni' => '15',
                'nazevObce' => 'Praha',
                'psc' => '11810',
                'kodStatu' => 'CZ',
            ],
        ]);

        self::assertNotNull($company->address);
        self::assertNotEmpty($company->address->text);
        self::assertSame('Praha', $company->address->city);
        self::assertSame('11810', $company->address->postalCode);
    }
}
