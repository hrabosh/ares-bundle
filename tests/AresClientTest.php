<?php

declare(strict_types=1);

namespace Lustrace\AresBundle\Tests;

use Lustrace\AresBundle\Client\AresClient;
use Lustrace\AresBundle\Client\AresClientOptions;
use Lustrace\AresBundle\Enum\Dataset;
use Lustrace\AresBundle\Enum\DatasetStatus;
use Lustrace\AresBundle\Normalizer\AresCompanyNormalizer;
use Lustrace\AresBundle\RateLimit\AresRateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class AresClientTest extends TestCase
{
    public function testGetEconomicSubjectOk(): void
    {
        $calls = [];

        $mock = new MockHttpClient(
            function (string $method, string $url, array $options) use (&$calls): MockResponse {
                $calls[] = [$method, $url];

                return new MockResponse(json_encode([
                    'ico' => '6947',
                    'obchodniJmeno' => 'Ministerstvo financí',
                    'dic' => 'CZ00006947',
                    'pravniForma' => '801',
                    'sidlo' => [
                        'textovaAdresa' => 'Letenská 525/15, 118 10 Praha 1',
                        'nazevObce' => 'Praha',
                        'psc' => '11810',
                    ],
                    'datumVzniku' => '1991-01-01',
                ], JSON_THROW_ON_ERROR), ['http_code' => 200]);
            },
            'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/'
        );

        $ares = $this->makeClient($mock);

        $result = $ares->getEconomicSubject('00006947', Dataset::ARES);

        self::assertSame(DatasetStatus::OK, $result->status);
        self::assertSame(200, $result->httpStatus);
        self::assertNotNull($result->company);
        self::assertSame('00006947', $result->company->icoCanonical);
        self::assertSame('Ministerstvo financí', $result->company->name);

        self::assertCount(1, $calls);
        self::assertSame('GET', $calls[0][0]);
        self::assertStringEndsWith('/ekonomicke-subjekty/6947', $calls[0][1]);
    }

    public function testFindCompanyByIcoBestCompanyFromSecondDataset(): void
    {
        $mock = new MockHttpClient(
            function (string $method, string $url, array $options): MockResponse {
                if (str_ends_with($url, '/ekonomicke-subjekty/6947')) {
                    return new MockResponse(json_encode([
                        'kod' => 'NENALEZENO',
                        'popis' => 'Record not found',
                    ], JSON_THROW_ON_ERROR), ['http_code' => 404]);
                }

                if (str_ends_with($url, '/ekonomicke-subjekty-res/6947')) {
                    return new MockResponse(json_encode([
                        'ico' => '6947',
                        'obchodniJmeno' => 'Ministerstvo financí',
                    ], JSON_THROW_ON_ERROR), ['http_code' => 200]);
                }

                return new MockResponse('Unexpected URL', ['http_code' => 500]);
            },
            'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/'
        );

        $ares = $this->makeClient($mock);

        $result = $ares->findCompanyByIco('6947', [Dataset::ARES, Dataset::RES]);

        self::assertSame('00006947', $result->icoCanonical);
        self::assertNotNull($result->bestCompany);
        self::assertSame(Dataset::RES, $result->bestCompany->dataset);
        self::assertSame('Ministerstvo financí', $result->bestCompany->name);

        $aresResult = $result->datasets['ares'];
        self::assertSame(DatasetStatus::NOT_FOUND, $aresResult->status);

        $resResult = $result->datasets['res'];
        self::assertSame(DatasetStatus::OK, $resResult->status);
    }

    private function makeClient(MockHttpClient $httpClient): AresClient
    {
        $rateLimiter = new AresRateLimiter(
            enabled: false,
            wait: false,
            key: 'test',
            factory: null,
        );

        $options = new AresClientOptions(
            baseUri: 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/',
            timeoutSeconds: 10.0,
            defaultDatasetCodes: ['ares'],
        );

        return new AresClient(
            httpClient: $httpClient,
            rateLimiter: $rateLimiter,
            normalizer: new AresCompanyNormalizer(),
            options: $options,
            logger: null,
        );
    }
}
