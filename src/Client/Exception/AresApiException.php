<?php

declare(strict_types=1);

namespace Hrabo\AresBundle\Client\Exception;

use Hrabo\AresBundle\DTO\AresError;
use Hrabo\AresBundle\Enum\Dataset;

final class AresApiException extends \RuntimeException
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly ?Dataset $dataset,
        public readonly ?AresError $aresError,
        string $message = 'ARES API request failed.',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
