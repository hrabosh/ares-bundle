<?php

declare(strict_types=1);

namespace Lustrace\AresBundle\Normalizer;

final class IcoNormalizer
{
    /**
     * Normalizes input IČO to:
     * - query value used in ARES URLs (no leading zeros),
     * - canonical value used in outputs (8 digits, left padded with zeros).
     *
     * @return array{query: string, canonical: string}
     */
    public static function normalize(string $ico): array
    {
        $ico = trim($ico);

        if (!preg_match('/^\d{1,8}$/', $ico)) {
            throw new \InvalidArgumentException(sprintf('Invalid IČO "%s". Expected 1-8 digits.', $ico));
        }

        $query = ltrim($ico, '0');
        if ($query === '') {
            $query = '0';
        }

        $canonical = str_pad($query, 8, '0', STR_PAD_LEFT);

        return [
            'query' => $query,
            'canonical' => $canonical,
        ];
    }
}
