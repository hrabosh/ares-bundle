<?php

declare(strict_types=1);

namespace Lustrace\AresBundle\Client;

use Lustrace\AresBundle\Dto\CompanyLookupResult;
use Lustrace\AresBundle\Dto\DatasetResult;
use Lustrace\AresBundle\Enum\Dataset;

interface AresClientInterface
{
    /**
     * Aggregate lookup across multiple datasets for a single IČO.
     *
     * @param list<Dataset|string>|null $datasets When null, defaults to configured datasets.
     */
    public function findCompanyByIco(string $ico, ?array $datasets = null): CompanyLookupResult;

    public function getEconomicSubject(string $ico, Dataset $dataset = Dataset::ARES): DatasetResult;

    /**
     * POST /{dataset}/vyhledat
     *
     * @param array<string, mixed> $filter
     * @return array<string, mixed>
     */
    public function searchEconomicSubjects(Dataset $dataset, array $filter): array;

    /**
     * POST /ciselniky-nazevniky/vyhledat
     *
     * @param array<string, mixed> $filter
     * @return array<string, mixed>
     */
    public function searchCodebooks(array $filter): array;

    /**
     * POST /standardizovane-adresy/vyhledat
     *
     * @param array<string, mixed> $filter
     * @return array<string, mixed>
     */
    public function searchStandardizedAddresses(array $filter): array;
}
