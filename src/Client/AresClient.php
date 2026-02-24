<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\Client;

use Hrabo\AresBundle\Client\Exception\AresApiException;
use Hrabo\AresBundle\DTO\AresError;
use Hrabo\AresBundle\DTO\CompanyLookupResult;
use Hrabo\AresBundle\DTO\DatasetResult;
use Hrabo\AresBundle\Enum\Dataset;
use Hrabo\AresBundle\Enum\DatasetStatus;
use Hrabo\AresBundle\Normalizer\AresCompanyNormalizer;
use Hrabo\AresBundle\Normalizer\IcoNormalizer;
use Hrabo\AresBundle\RateLimit\AresRateLimiter;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AresClient implements AresClientInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly AresRateLimiter $rateLimiter,
        private readonly AresCompanyNormalizer $normalizer,
        private readonly AresClientOptions $options,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new \Psr\Log\NullLogger();
    }

    public function findCompanyByIco(string $ico, ?array $datasets = null): CompanyLookupResult
    {
        $icoNorm = IcoNormalizer::normalize($ico);
        $icoQuery = $icoNorm['query'];
        $icoCanonical = $icoNorm['canonical'];

        $datasetsResolved = $this->resolveDatasets($datasets);

        $results = [];
        $best = null;

        foreach ($datasetsResolved as $dataset) {
            $datasetResult = $this->getEconomicSubject($icoQuery, $dataset);

            $results[$dataset->value] = $datasetResult;

            if (null === $best && $datasetResult->isOk() && null !== $datasetResult->company) {
                $best = $datasetResult->company;
            }
        }

        return new CompanyLookupResult(
            icoCanonical: $icoCanonical,
            bestCompany: $best,
            datasets: $results,
            fetchedAt: new \DateTimeImmutable(),
        );
    }

    public function getEconomicSubject(string $ico, Dataset $dataset = Dataset::ARES): DatasetResult
    {
        $icoNorm = IcoNormalizer::normalize($ico);
        $icoQuery = $icoNorm['query'];
        $icoCanonical = $icoNorm['canonical'];

        $path = sprintf('%s/%s', $dataset->endpointPrefix(), $icoQuery);

        $start = microtime(true);
        $this->rateLimiter->throttle();

        try {
            $response = $this->httpClient->request('GET', $path, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);
        } catch (TransportExceptionInterface $e) {
            return new DatasetResult(
                dataset: $dataset,
                status: DatasetStatus::ERROR,
                httpStatus: 0,
                latencyMs: (microtime(true) - $start) * 1000,
                company: null,
                error: new AresError(code: 'TRANSPORT', subCode: null, description: $e->getMessage()),
                raw: null,
            );
        }

        $httpStatus = 0;
        $content = null;
        try {
            $httpStatus = $response->getStatusCode();
            $content = $response->getContent(false);
        } catch (TransportExceptionInterface $e) {
            return new DatasetResult(
                dataset: $dataset,
                status: DatasetStatus::ERROR,
                httpStatus: 0,
                latencyMs: (microtime(true) - $start) * 1000,
                company: null,
                error: new AresError(code: 'TRANSPORT', subCode: null, description: $e->getMessage()),
                raw: null,
            );
        } finally {
            $latencyMs = (microtime(true) - $start) * 1000;
        }

        $data = $this->decodeJson($content);

        if ($httpStatus === 200 && is_array($data)) {
            /** @var array<string, mixed> $data */
            $company = $this->normalizer->normalize($dataset, $icoCanonical, $data);

            return new DatasetResult(
                dataset: $dataset,
                status: DatasetStatus::OK,
                httpStatus: 200,
                latencyMs: $latencyMs,
                company: $company,
                error: null,
                raw: $data,
            );
        }

        if ($httpStatus === 404) {
            return new DatasetResult(
                dataset: $dataset,
                status: DatasetStatus::NOT_FOUND,
                httpStatus: 404,
                latencyMs: $latencyMs,
                company: null,
                error: $this->parseAresError($data),
                raw: is_array($data) ? $data : null,
            );
        }

        return new DatasetResult(
            dataset: $dataset,
            status: DatasetStatus::ERROR,
            httpStatus: $httpStatus,
            latencyMs: $latencyMs,
            company: null,
            error: $this->parseAresError($data) ?? new AresError(code: 'HTTP_'.$httpStatus, subCode: null, description: 'Unexpected HTTP status.'),
            raw: is_array($data) ? $data : null,
        );
    }

    public function searchEconomicSubjects(Dataset $dataset, array $filter): array
    {
        return $this->postOrFail($dataset, sprintf('%s/vyhledat', $dataset->endpointPrefix()), $filter);
    }

    public function searchCodebooks(array $filter): array
    {
        return $this->postOrFail(null, 'ciselniky-nazevniky/vyhledat', $filter);
    }

    public function searchStandardizedAddresses(array $filter): array
    {
        return $this->postOrFail(null, 'standardizovane-adresy/vyhledat', $filter);
    }

    /**
     * @param array<string, mixed> $json
     * @return array<string, mixed>
     */
    private function postOrFail(?Dataset $dataset, string $path, array $json): array
    {
        $start = microtime(true);
        $this->rateLimiter->throttle();

        try {
            $response = $this->httpClient->request('POST', $path, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'json' => $json,
            ]);
        } catch (TransportExceptionInterface $e) {
            throw new AresApiException(
                httpStatus: 0,
                dataset: $dataset,
                aresError: new AresError(code: 'TRANSPORT', subCode: null, description: $e->getMessage()),
                message: 'ARES API transport error.',
                previous: $e,
            );
        }

        $httpStatus = $response->getStatusCode();
        $content = $response->getContent(false);
        $latencyMs = (microtime(true) - $start) * 1000;

        $data = $this->decodeJson($content);
        if (200 !== $httpStatus || !is_array($data)) {
            $err = $this->parseAresError($data) ?? new AresError(code: 'HTTP_'.$httpStatus, subCode: null, description: 'ARES API request failed.');

            $this->logger->warning('ARES API request failed', [
                'httpStatus' => $httpStatus,
                'path' => $path,
                'dataset' => $dataset?->value,
                'latencyMs' => $latencyMs,
                'aresError' => $err->toArray(),
            ]);

            throw new AresApiException(
                httpStatus: $httpStatus,
                dataset: $dataset,
                aresError: $err,
                message: sprintf('ARES API request failed with HTTP %d.', $httpStatus),
            );
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * @param list<Dataset|string>|null $datasets
     * @return list<Dataset>
     */
    private function resolveDatasets(?array $datasets): array
    {
        $datasets = $datasets ?? $this->options->defaultDatasetCodes;

        $out = [];
        foreach ($datasets as $d) {
            if ($d instanceof Dataset) {
                $out[] = $d;

                continue;
            }
            if (is_string($d)) {
                $out[] = Dataset::fromCode($d);

                continue;
            }

            throw new \InvalidArgumentException('Datasets must be Dataset enums or strings.');
        }

        // preserve order, unique by value
        $seen = [];
        $uniq = [];
        foreach ($out as $d) {
            if (isset($seen[$d->value])) {
                continue;
            }
            $seen[$d->value] = true;
            $uniq[] = $d;
        }

        return $uniq;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(?string $content): ?array
    {
        if (null === $content) {
            return null;
        }

        $content = trim($content);
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return null;
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    private function parseAresError(?array $data): ?AresError
    {
        if (!is_array($data)) {
            return null;
        }

        $code = $this->firstString($data, ['kod', 'code', 'errorCode']);
        $subCode = $this->firstString($data, ['subKod', 'subCode']);
        $desc = $this->firstString($data, ['popis', 'description', 'message', 'chyba']);

        if (null === $code && null === $desc && null === $subCode) {
            return null;
        }

        return new AresError($code, $subCode, $desc, $data);
    }

    /**
     * @param array<string, mixed> $arr
     * @param list<string> $keys
     */
    private function firstString(array $arr, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $arr)) {
                continue;
            }

            $v = $arr[$k];
            if (is_string($v)) {
                $v = trim($v);

                return $v !== '' ? $v : null;
            }

            if (is_int($v) || is_float($v)) {
                return (string) $v;
            }
        }

        return null;
    }
}
