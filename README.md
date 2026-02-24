# LustraceAresBundle

Symfony bundle integrating the official **ARES (Administrativní registr ekonomických subjektů)** REST API.

The bundle focuses on:
- **Company detail by IČO** from ARES “core” and/or individual datasets (RES, VR, RŽP, ROS, RCNS, RPSH, CEÚ, RŠ, SZR, NRPZS).
- **Configurable retries** (HTTP client retry strategy + `Retry-After` support).
- **Configurable rate limiting** (token bucket limiter) to avoid API blocking.
- **Standardized output** (DTOs + `toArray()`).

> API documentation:
> - OpenAPI: https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/v3/api-docs  
> - Swagger UI: https://ares.gov.cz/swagger-ui/  
> - Developer info: https://ares.gov.cz/stranky/vyvojar-info  
> - Technical doc (catalog of public services): https://mf.gov.cz/assets/attachments/2024-02-16_ARES-Technical-documentation-Catalog-of-public-services_v02.pdf

---

## Installation

```bash
composer require lustrace/ares-bundle
```

Enable the bundle (if not using Flex auto-discovery):

```php
// config/bundles.php
return [
    // ...
    Lustrace\AresBundle\LustraceAresBundle::class => ['all' => true],
];
```

---

## Configuration

Create `config/packages/lustrace_ares.yaml`:

```yaml
lustrace_ares:
  base_uri: 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/'
  timeout_seconds: 10

  retry:
    max_retries: 3
    delay_ms: 250
    multiplier: 2.0
    max_delay_ms: 5000
    jitter: 0.1
    status_codes: [0, 429, 500, 502, 503, 504]

  rate_limit:
    enabled: true
    # Token bucket limiter:
    limit: 10
    interval: '1 second'
    burst: 20
    wait: true
    key: 'ares-outgoing' # all workers share this key via cache.app

  datasets:
    # Default datasets used by findCompanyByIco() when not provided explicitly:
    - ares
    - res
    - vr
    - rzp
    - ros
    - rcns
    - rpsh
    - ceu
    - rs
    - szr
    - nrpzs
```

Notes:
- `rate_limit.key` is stored via `cache.app` and shared across workers/servers if `cache.app` uses a shared backend (Redis, Memcached, ...).
- For maximum safety against ARES blocking, prefer a shared cache backend for `cache.app` (Redis).

---

## Usage

### 1) Aggregate lookup by IČO across datasets

```php
use Lustrace\AresBundle\Client\AresClientInterface;

final class SomeService
{
    public function __construct(private AresClientInterface $ares) {}

    public function run(): void
    {
        $result = $this->ares->findCompanyByIco('00006947'); // or '6947'
        $best = $result->bestCompany; // NormalizedCompany|null

        // Array for storage/export:
        $payload = $result->toArray();
    }
}
```

### 2) Single dataset detail (GET {ico})

```php
use Lustrace\AresBundle\Enum\Dataset;

$datasetResult = $ares->getEconomicSubject('6947', Dataset::RES);

if ($datasetResult->isOk()) {
    $company = $datasetResult->company; // NormalizedCompany
    $raw = $datasetResult->raw;         // array
}
```

### 3) Search (POST vyhledat)

```php
use Lustrace\AresBundle\Enum\Dataset;

$search = $ares->searchEconomicSubjects(Dataset::ARES, [
    'start' => 0,
    'pocet' => 10,
    'obchodniJmeno' => 'Ministerstvo financí',
]);

// $search is raw ARES response array.
```

### 4) Codebooks (POST vyhledat)

```php
$codebooks = $ares->searchCodebooks([
    'start' => 0,
    'pocet' => 50,
    'kodCiselniku' => 'PravniForma',
]);
```

### 5) Standardized addresses (POST vyhledat)

```php
$addresses = $ares->searchStandardizedAddresses([
    'start' => 0,
    'pocet' => 10,
    'textovaAdresa' => 'Letenská 525/15, Praha 1',
]);
```

---

## Standardized output

- `CompanyLookupResult` contains:
  - `icoCanonical` (8 digits, left padded with zeros)
  - `bestCompany` (first successful `NormalizedCompany` in chosen dataset order)
  - `datasets` map: dataset code → `DatasetResult`
- `DatasetResult` contains:
  - `status` (`ok|not_found|error`)
  - `httpStatus`, `latencyMs`
  - `raw` (decoded JSON, when available)
  - `company` (normalized fields, when available)
  - `error` (parsed API error, when available)

---

## Tests

```bash
composer install
vendor/bin/phpunit
```

---

## Code style (phpcs/phpcbf)

```bash
# show violations
vendor/bin/phpcs

# auto-fix what can be fixed
vendor/bin/phpcbf
```

The ruleset is in `phpcs.xml` and uses the Symfony PHP_CodeSniffer standard.

---

## Versioning

This is an initial bundle skeleton intended to be extended inside your application.
PRs welcome.
