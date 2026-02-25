<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\Normalizer;

use Hrabo\AresBundle\DTO\Address;
use Hrabo\AresBundle\DTO\NormalizedCompany;
use Hrabo\AresBundle\Enum\Dataset;

final class AresCompanyNormalizer {
    /**
     * @param array<string, mixed> $raw
     */
    public function normalize(Dataset $dataset, string $icoCanonical, array $raw): NormalizedCompany {
        $name = $this->firstString($raw, ['obchodniJmeno', 'obchodniJmenoRos', 'nazev', 'firma', 'jmeno']);
        $vatId = $this->firstString($raw, ['dic', 'vatId', 'dph']);
        $legalFormCode = $this->firstScalar($raw, ['pravniForma', 'pravniFormaRos', 'kodPravniForma']);

        $establishedAt = $this->parseDate($this->firstString($raw, ['datumVzniku', 'vznik', 'datumVznikuSubjektu']));
        $terminatedAt = $this->parseDate($this->firstString($raw, ['datumZaniku', 'zanik', 'datumZanikuSubjektu']));

        $address = $this->normalizeAddress($raw);

        $extra = [];
        foreach (['financniUrad', 'datovaSchranka', 'identifikatorDs', 'czNace', 'nace', 'datumAktualizace'] as $k) {
            if (array_key_exists($k, $raw)) {
                $extra[$k] = $raw[$k];
            }
        }

        return new NormalizedCompany(
            icoCanonical: $icoCanonical,
            dataset: $dataset,
            name: $name,
            vatId: $vatId,
            legalFormCode: $legalFormCode,
            address: $address,
            establishedAt: $establishedAt,
            terminatedAt: $terminatedAt,
            extra: $extra,
        );
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function normalizeAddress(array $raw): ?Address {
        $addr = NULL;

        if (isset($raw['sidlo']) && is_array($raw['sidlo'])) {
            $addr = $raw['sidlo'];
        } elseif (isset($raw['adresa']) && is_array($raw['adresa'])) {
            $addr = $raw['adresa'];
        } elseif (isset($raw['trvaleBydliste']) && is_array($raw['trvaleBydliste'])) {
            $addr = $raw['trvaleBydliste'];
        }

        if (!is_array($addr)) {
            return NULL;
        }

        /** @var array<string, mixed> $addr */
        $text = $this->firstString($addr, ['textovaAdresa', 'adresaTxt', 'text', 'adresa']);
        $street = $this->firstString($addr, ['nazevUlice', 'ulice', 'street']);
        $house = $this->firstScalar($addr, ['cisloDomovni', 'houseNumber', 'cisloDomu']);
        $orient = $this->firstScalar($addr, ['cisloOrientacni', 'orientationNumber']);
        $orientLetter = $this->firstString($addr, ['cisloOrientacniPismeno', 'orientationNumberLetter']);
        $cityPart = $this->firstString($addr, ['nazevCastiObce', 'castObce', 'cityPart']);
        $city = $this->firstString($addr, ['nazevObce', 'obec', 'city']);
        $psc = $this->firstScalar($addr, ['psc', 'postalCode']);
        $country = $this->firstString($addr, ['kodStatu', 'countryCode']);

        if (NULL === $text) {
            $housePart = $house;
            if (NULL !== $house && NULL !== $orient) {
                $housePart = $house.'/'.$orient.($orientLetter ?? '');
            }

            $text = trim(implode(' ', array_filter([
                $street,
                $housePart,
                $cityPart,
                $city,
                $psc,
                $country,
            ], static fn ($v): bool => NULL !== $v && $v !== '')));
        }
        if ($text === '') {
            $text = NULL;
        }

        return new Address(
            text: $text,
            street: $street,
            houseNumber: $house,
            orientationNumber: $orient,
            orientationNumberLetter: $orientLetter,
            cityPart: $cityPart,
            city: $city,
            postalCode: $psc,
            countryCode: $country,
        );
    }

    /**
     * @param array<string, mixed> $arr
     * @param list<string>         $keys
     */
    private function firstString(array $arr, array $keys): ?string {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $arr)) {
                continue;
            }

            $v = $arr[$k];
            if (is_string($v)) {
                $v = trim($v);

                return $v !== '' ? $v : NULL;
            }
        }

        return NULL;
    }

    /**
     * @param array<string, mixed> $arr
     * @param list<string>         $keys
     */
    private function firstScalar(array $arr, array $keys): ?string {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $arr)) {
                continue;
            }

            $v = $arr[$k];
            if (is_string($v) || is_int($v) || is_float($v)) {
                $s = trim((string) $v);

                return $s !== '' ? $s : NULL;
            }
        }

        return NULL;
    }

    private function parseDate(?string $v): ?\DateTimeImmutable {
        if (NULL === $v || $v === '') {
            return NULL;
        }

        $v = trim($v);

        foreach (['Y-m-d', DATE_ATOM, 'Y-m-d\TH:i:sP', 'Y-m-d\TH:i:s.vP'] as $fmt) {
            $dt = \DateTimeImmutable::createFromFormat($fmt, $v);
            if ($dt instanceof \DateTimeImmutable) {
                return $dt;
            }
        }

        try {
            return new \DateTimeImmutable($v);
        } catch (\Throwable) {
            return NULL;
        }
    }
}
