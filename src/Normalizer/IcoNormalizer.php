<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\Normalizer;

final class IcoNormalizer {

	/**
     * Normalizes input IČO by trimming whitespace and validating format (1-8 digits).
     *
     * @return array{query: string, canonical: string}
     */
    public static function normalize(string $ico): string {
        $ico = trim($ico);

        if (!preg_match('/^\d{1,8}$/', $ico)) {
            throw new \InvalidArgumentException(sprintf('Invalid IČO "%s". Expected 1-8 digits.', $ico));
        }

		return $ico;
    }
}
