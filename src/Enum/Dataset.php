<?php

declare(strict_types=1);

namespace Lustrace\AresBundle\Enum;

/**
 * ARES public services / datasets used for economic subject lookups.
 *
 * Dataset codes are used in configuration and output.
 */
enum Dataset: string
{
    case ARES = 'ares';
    case RES = 'res';
    case NRPZS = 'nrpzs';
    case VR = 'vr';
    case RZP = 'rzp';
    case ROS = 'ros';
    case RCNS = 'rcns';
    case RPSH = 'rpsh';
    case CEU = 'ceu';
    case RS = 'rs';
    case SZR = 'szr';

    /**
     * Endpoint prefix under the base URI (https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/).
     */
    public function endpointPrefix(): string
    {
        return match ($this) {
            self::ARES => 'ekonomicke-subjekty',
            self::RES => 'ekonomicke-subjekty-res',
            self::NRPZS => 'ekonomicke-subjekty-nrpzs',
            self::VR => 'ekonomicke-subjekty-vr',
            self::RZP => 'ekonomicke-subjekty-rzp',
            self::ROS => 'ekonomicke-subjekty-ros',
            self::RCNS => 'ekonomicke-subjekty-rcns',
            self::RPSH => 'ekonomicke-subjekty-rpsh',
            self::CEU => 'ekonomicke-subjekty-ceu',
            self::RS => 'ekonomicke-subjekty-rs',
            self::SZR => 'ekonomicke-subjekty-szr',
        };
    }

    /**
     * @return list<self>
     */
    public static function companyDatasets(): array
    {
        return [
            self::ARES,
            self::RES,
            self::VR,
            self::RZP,
            self::ROS,
            self::RCNS,
            self::RPSH,
            self::CEU,
            self::RS,
            self::SZR,
            self::NRPZS,
        ];
    }

    public static function fromCode(string $code): self
    {
        $code = strtolower(trim($code));

        foreach (self::cases() as $case) {
            if ($case->value === $code) {
                return $case;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unknown dataset code "%s". Allowed: %s', $code, implode(', ', array_map(static fn (self $d): string => $d->value, self::cases()))));
    }
}
